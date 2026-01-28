<?php
/**
 * Standalone unit tests for Inventory Locker
 * Tests core functions without WordPress dependencies
 */

echo "=== Inventory Locker Unit Tests ===\n\n";

$tests_passed = 0;
$tests_failed = 0;

function test($name, $condition) {
    global $tests_passed, $tests_failed;
    if ($condition) {
        echo "✅ PASS: $name\n";
        $tests_passed++;
    } else {
        echo "❌ FAIL: $name\n";
        $tests_failed++;
    }
}

// Mock WordPress functions
function get_option($key, $default = false) {
    static $options = [
        'inventory_locker_duration' => 15,
        'inventory_locker_configured' => true,
        'inventory_locker_tracked_products' => [],
    ];
    return $options[$key] ?? $default;
}

function get_transient($key) {
    global $transients;
    return $transients[$key] ?? false;
}

function set_transient($key, $value, $expiration) {
    global $transients;
    $transients[$key] = $value;
    return true;
}

function delete_transient($key) {
    global $transients;
    unset($transients[$key]);
    return true;
}

function update_option($key, $value, $autoload = true) {
    return true;
}

function mock_class_exists($class) {
    return $class === 'WooCommerce';
}

function mock_defined($name) {
    return false;
}

$transients = [];

// Define constants
define('MINUTE_IN_SECONDS', 60);
define('DAY_IN_SECONDS', 86400);
define('INVENTORY_LOCKER_DEFAULT_DURATION', 15);

// Test 1: Lock duration calculation
echo "--- Lock Duration Tests ---\n";

function inventory_locker_get_lock_duration_test() {
    $minutes = get_option('inventory_locker_duration', 0);
    if ($minutes <= 0) {
        return INVENTORY_LOCKER_DEFAULT_DURATION * MINUTE_IN_SECONDS;
    }
    return intval($minutes) * MINUTE_IN_SECONDS;
}

$duration = inventory_locker_get_lock_duration_test();
test("Lock duration returns 15 minutes in seconds (900)", $duration === 900);

// Test 2: Product locks storage
echo "\n--- Lock Storage Tests ---\n";

function inventory_locker_get_product_locks_test($product_id) {
    $locks = get_transient("inventory_locks_{$product_id}");
    if (!is_array($locks)) {
        $locks = [];
    }
    
    $now = time();
    foreach ($locks as $session_id => $lock_data) {
        if ($lock_data['expires'] < $now) {
            unset($locks[$session_id]);
        }
    }
    
    return $locks;
}

function inventory_locker_save_product_locks_test($product_id, $locks) {
    if (empty($locks)) {
        delete_transient("inventory_locks_{$product_id}");
    } else {
        $duration = inventory_locker_get_lock_duration_test();
        set_transient("inventory_locks_{$product_id}", $locks, $duration + MINUTE_IN_SECONDS);
    }
}

// Test creating a lock
$test_product_id = 123;
$test_session_id = 'wc_test_session';
$test_locks = [
    $test_session_id => [
        'quantity' => 5,
        'expires' => time() + 900,
    ]
];

inventory_locker_save_product_locks_test($test_product_id, $test_locks);
$retrieved_locks = inventory_locker_get_product_locks_test($test_product_id);

test("Lock can be saved and retrieved", isset($retrieved_locks[$test_session_id]));
test("Lock quantity is correct", $retrieved_locks[$test_session_id]['quantity'] === 5);

// Test 3: Expired lock cleanup
echo "\n--- Expiration Tests ---\n";

$expired_locks = [
    'expired_session' => [
        'quantity' => 3,
        'expires' => time() - 100, // Already expired
    ],
    'valid_session' => [
        'quantity' => 2,
        'expires' => time() + 500,
    ]
];

inventory_locker_save_product_locks_test(456, $expired_locks);
$cleaned_locks = inventory_locker_get_product_locks_test(456);

test("Expired locks are removed", !isset($cleaned_locks['expired_session']));
test("Valid locks are kept", isset($cleaned_locks['valid_session']));

// Test 4: Locked quantity calculation
echo "\n--- Quantity Calculation Tests ---\n";

function inventory_locker_get_locked_quantity_test($product_id, $exclude_session_id = null) {
    $locks = inventory_locker_get_product_locks_test($product_id);
    $total = 0;
    
    foreach ($locks as $session_id => $lock_data) {
        if ($exclude_session_id && $session_id === $exclude_session_id) {
            continue;
        }
        $total += $lock_data['quantity'];
    }
    
    return $total;
}

// Set up multiple locks
$multi_locks = [
    'session_a' => ['quantity' => 3, 'expires' => time() + 500],
    'session_b' => ['quantity' => 2, 'expires' => time() + 500],
    'session_c' => ['quantity' => 5, 'expires' => time() + 500],
];
inventory_locker_save_product_locks_test(789, $multi_locks);

$total_locked = inventory_locker_get_locked_quantity_test(789);
test("Total locked quantity is correct (10)", $total_locked === 10);

$excluding_b = inventory_locker_get_locked_quantity_test(789, 'session_b');
test("Excluding session_b gives 8", $excluding_b === 8);

// Test 5: Available stock calculation
echo "\n--- Available Stock Tests ---\n";

function inventory_locker_get_available_stock_test($actual_stock, $product_id, $exclude_session_id = null) {
    $locked = inventory_locker_get_locked_quantity_test($product_id, $exclude_session_id);
    return max(0, $actual_stock - $locked);
}

$actual_stock = 15;
$available = inventory_locker_get_available_stock_test($actual_stock, 789);
test("Available stock calculation (15 - 10 = 5)", $available === 5);

$available_for_b = inventory_locker_get_available_stock_test($actual_stock, 789, 'session_b');
test("Available stock for session_b (15 - 8 = 7)", $available_for_b === 7);

// Test 6: Edge cases
echo "\n--- Edge Case Tests ---\n";

$low_stock = 5;
$available_low = inventory_locker_get_available_stock_test($low_stock, 789);
test("Available stock doesn't go negative (max 0)", $available_low === 0);

// Test empty locks
$empty_locks = inventory_locker_get_product_locks_test(999);
test("Non-existent product returns empty array", is_array($empty_locks) && empty($empty_locks));

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: $tests_passed\n";
echo "Failed: $tests_failed\n";

if ($tests_failed === 0) {
    echo "\n✅ All tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some tests failed.\n";
    exit(1);
}

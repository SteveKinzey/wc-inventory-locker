=== Armario de inventario ===
Colaboradores: stevekinzey
Enlace para donaciones: https://sk-america.com
Etiquetas: woocommerce, surecart, inventario, carrito, gestión de existencias, reserva de existencias, bloqueo de existencias, sobreventa.
Requisitos mínimos: 5.8
Probado hasta: 6.7
Requiere PHP: 7.4
Etiqueta estable: 2.3.0
Licencia: GPLv2 o posterior
URI de la licencia: https://www.gnu.org/licenses/gpl-2.0.html

¡Deja de vender en exceso! Bloquea el inventario cuando se añaden productos al carrito. Funciona con WooCommerce y SureCart.

== Descripción ==

**Inventory Locker** evita la sobreventa al reservar el stock del producto en el momento en que se añade al carrito. Perfecto para ventas flash, ediciones limitadas y lanzamientos de productos de alta demanda.

= El problema: la sobreventa destruye la confianza del cliente =

Imagina esto: estás realizando una venta flash de unas zapatillas de edición limitada con 50 pares en stock. En cuestión de minutos, 200 clientes las añaden a su carrito. Sin el bloqueo de inventario, los 200 clientes proceden a la compra, pero solo se pueden completar 50 pedidos.

¿El resultado? 150 clientes enfadados, correos electrónicos de cancelación, solicitudes de reembolso, críticas negativas y un daño duradero a la reputación de su marca.

= Casos de uso en el mundo real =

**Tiendas de merchandising de conciertos**: vende merchandising limitado de giras sin sobrevender. Cuando se agote, se agotará.

**Revendedores de zapatillas y ropa urbana**: gestiona los lanzamientos más esperados, en los que la demanda supera la oferta en 10 veces o más.

**Tiendas de videojuegos y coleccionables**: gestiona de forma justa los pedidos anticipados de figuras raras y ediciones limitadas.

**Clubes de vino y productos asignados**: el primero en añadir al carrito, el primero en comprar. Se acabó pelearse por las últimas botellas en la caja.

**Entradas para eventos**: reserva los asientos en el momento en que se seleccionan, evitando las reservas dobles.

= Características principales =

* **Bloqueo de existencias en tiempo real**: reserva el inventario al instante cuando se añade al carrito.
* **Duración del bloqueo configurable**: establece un tiempo de espera de 1 minuto a 24 horas.
* **Liberación automática**: las existencias vuelven a estar disponibles si se abandona el carrito o se elimina el artículo.
* **Compatibilidad con múltiples plataformas**: funciona con WooCommerce Y SureCart.
* **Compatibilidad con productos variables**: bloquea a nivel de variación para un inventario preciso.
* **Panel de administración**: supervise los bloqueos activos y configure los ajustes.
* **Ajustes protegidos con contraseña**: evite cambios accidentales.
* **Sesión consciente**: el bloqueo de cada cliente es independiente y seguro.

= Cómo funciona =

1. El cliente añade el producto al carrito → El stock se bloquea inmediatamente.
2. Otros clientes ven reducida la cantidad disponible.
3. Si el cliente realiza la compra → Se libera el bloqueo y se produce la reducción normal del stock.
4. Si el cliente abandona el carrito → El bloqueo caduca y el stock vuelve a estar disponible.

== Instalación ==

1. Sube los archivos del plugin a `/wp-content/plugins/inventory-locker` o instálalo a través del administrador de WordPress.
2. Actívalo a través del menú «Plugins».
3. Ve a WooCommerce > Inventory Locker (o SureCart > Inventory Locker) para configurarlo.
4. Establece la duración del bloqueo que desees y haz clic en «Activar plugin».

**Importante:** El plugin requiere una configuración inicial antes de activarse. Esto garantiza que elijas conscientemente el tiempo de bloqueo adecuado para tu tienda.

== Preguntas frecuentes ==

= ¿Esto reservará el stock indefinidamente? =
No. El stock se mantiene durante el tiempo que hayas configurado (por defecto: 15 minutos) y se libera automáticamente si el cliente no completa el proceso de pago.

= ¿Es compatible con productos variables? =
¡Sí! El inventario se bloquea a nivel de variación, por lo que cada talla/color/opción se rastrea de forma independiente.

= ¿Qué ocurre si un cliente abandona su carrito? =
El bloqueo caduca tras el periodo de tiempo configurado y el stock vuelve automáticamente al inventario disponible.

= ¿Puedo ver cuántos artículos están bloqueados actualmente? =
¡Sí! La página de configuración del administrador muestra el recuento de bloqueos activos y qué productos tienen reservas.

= ¿Funciona con SureCart? =
¡Sí! La versión 2.0+ es compatible tanto con WooCommerce como con SureCart con detección automática de la plataforma.

= ¿Qué duración de bloqueo debo utilizar? =
* Ventas flash/lanzamientos promocionales: 5-10 minutos
* Comercio electrónico estándar: 15-30 minutos  
* Artículos de alto valor: 30-60 minutos
* B2B/basado en presupuestos: más de 60 minutos

== Capturas de pantalla ==

1. Página de configuración del administrador que muestra la configuración del bloqueo
2. Panel de control de bloqueos activos
3. Notificación al cliente cuando el stock es limitado

== Registro de cambios ==

= 2.3.0 =
* Seguridad: se ha añadido la verificación nonce a los puntos finales de la API REST
* Seguridad: se ha añadido la limitación de velocidad (30 solicitudes/minuto por IP) para evitar el abuso de bots
* Se ha añadido la detección de IP con reconocimiento de proxy (Cloudflare, nginx, etc.)

= 2.2.0 =
* Se ha añadido el encabezado Text Domain para la traducción completa/compatibilidad con i18n.
* Se ha corregido el formato del archivo de licencia para una detección adecuada de GitHub (GPL-2.0).
* Se ha renombrado la carpeta de activos para la compatibilidad con WordPress.org.
* Las actualizaciones de la cantidad del carrito ahora ajustan correctamente las cantidades de inventario bloqueadas.

= 2.1 =
* Se ha cambiado el nombre del complemento a «Inventory Locker».
* Se han actualizado todas las referencias a archivos y carpetas.

= 2.0 =
* Se ha añadido compatibilidad con SureCart con integración de la API REST.
* Arquitectura multiplataforma con detección automática.
* Integración de JavaScript para eventos del carrito de SureCart.
* Compatibilidad con versiones anteriores de instalaciones existentes de WooCommerce.

= 1.6 =
* Se ha mejorado la fiabilidad del desbloqueo en el proceso de pago.
* Se han añadido múltiples ganchos de pago para varias pasarelas de pago.
* Se almacena el ID de sesión en el pedido para el procesamiento asíncrono de pagos.

= 1.5 =
* Se ha añadido una página de configuración del administrador con duración del bloqueo configurable.
* Se requiere la confirmación de la contraseña para cambiar la configuración.
* Asistente de configuración para la primera configuración.

= 1.4 =
* Se ha añadido la gestión de la caducidad de la sesión.
* Tarea cron de limpieza cada hora para bloqueos huérfanos.
* Se ha mejorado la limpieza del bloqueo al cerrar la sesión y al cambiar de sesión.

= 1.3 =
* Se ha corregido la aplicación del bloqueo con almacenamiento transitorio compartido.
* Se ha añadido la validación de añadir al carrito frente al inventario bloqueado.
* Compatibilidad total con variaciones.

= 1.2 =
* Se ha cambiado la licencia a GPLv2 para cumplir con WordPress.org.
* Se ha mejorado la lógica de bloqueo y restauración.

= 1.1 =
* Versión estable inicial

== Aviso de actualización ==

= 2.3.0 =
Actualización de seguridad: añade protección contra bots y limitación de velocidad a los puntos finales REST. Recomendado para todos los usuarios.

= 2.2.0 =
Compatibilidad mejorada con WordPress.org y compatibilidad con traducciones. Actualización recomendada para todos los usuarios.

= 2.1 =
El plugin ha cambiado de nombre a «Inventory Locker». Se conservan todas las funciones y es compatible con versiones anteriores.

= 2.0 =
¡Actualización importante! Ahora es compatible con SureCart además de con WooCommerce. Usuarios actuales de WooCommerce: no es necesario realizar ninguna acción.

== Créditos ==

Complemento desarrollado y mantenido por [Steve Kinzey](https://github.com/SteveKinzey)

== Licencia ==

Este plugin se distribuye bajo la licencia GPLv2. Consulte LICENSE para obtener más detalles.

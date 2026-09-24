ERP Pupusería Doña Mary — v1.1.0
Sistema de gestión (ERP) para Pupusería Doña Mary: punto de venta, control de ingredientes, recetas, productos, reportes de ventas y administración de usuarios.

📋 Integrantes del equipo
Jared Eliezer Cabrera Catalán — Lead Technician & Systems Developer (diagnóstico técnico, indexación SQL, refactorización de consultas PHP)
Natalia Abigail Morán Sánchez — Service Manager & Client Relations (gestión de alcance, plan de mantenimiento, documentación de entrega)
Oscar Mauricio Magaña Morán — Business Owner / Restaurant General Manager (dueño del negocio, validación y aceptación del sistema)
Cristian Jhovanny Sintigo Jiménez — Support Officer & User Trainer / Cashier (backups, pruebas unitarias, capacitación de usuario final)
🆕 Mejoras de la versión 1.1.0
Seguridad
Se eliminó un bypass de sesión que permitía entrar al módulo de Ingredientes sin iniciar sesión.
Se agregó verificación de sesión en Recetas, que antes estaba completamente desprotegida.
Se corrigió un error de lógica en el login que impedía mostrar el mensaje de "credenciales incorrectas" ante intentos fallidos.
Navegación
Se corrigió el formulario de login, que enviaba los datos a una página estática (index.php) en lugar de procesarlos realmente — esto causaba que el usuario quedara atrapado sin poder entrar a ningún módulo.
index.php ahora funciona como enrutador: redirige automáticamente al dashboard si hay sesión activa, o al login si no la hay.
Se unificaron todos los enlaces de "Cerrar Sesión" para que realmente destruyan la sesión, y se agregaron en pantallas donde faltaban (Ingredientes, Recetas, Usuarios).
Rendimiento
Se detectó que la tabla detalles_pedidos no tenía índice en la columna id_producto, forzando un escaneo completo de tabla en cada JOIN con productos y pedidos.
Se creó el índice idx_producto, reduciendo el tiempo de consulta de 2.10 segundos a 0.15 segundos (-92.8%) y mejorando la velocidad de creación de órdenes en un 53.3%.
Datos y catálogos
Se ampliaron las unidades de medida para ingredientes: peso (gramos, kilogramos, libras, onzas), volumen (mililitros, litros) y unidades.
Trazabilidad de ventas
Cada pantalla del sistema ahora muestra qué usuario tiene la sesión iniciada y con qué rol (Administrador o Cajero).
Los reportes de ventas y los tickets PDF ahora muestran el nombre y el rol de la persona que procesó cada venta.
Idioma
Traducción completa de la interfaz (menús, botones, formularios, mensajes y tickets) al inglés.
Cuentas oficiales del sistema
Se crearon tres cuentas oficiales con contraseña cifrada (bcrypt):

Usuario	Contraseña	Rol
mary_admin	mary123	Administrator
chepe_cajero	mary123	Cashier
ana_cajera	mary123	Cashier
⚙️ Instalación
Requisitos
XAMPP (Apache + MySQL + PHP 8.x)
Navegador web
Pasos
Copiar el proyecto Extrae la carpeta erp_pupuseria dentro de: C:\xampp\htdocs
Debe quedar como C:\xampp\htdocs\erp_pupuseria\.

Iniciar Apache y MySQL Abre el Panel de Control de XAMPP y da clic en Start junto a Apache y MySQL.

Crear la base de datos Abre phpMyAdmin (http://localhost/phpmyadmin) y crea una base de datos llamada erp_pupuseria (o la que corresponda) con las tablas del sistema (usuarios, productos, ingredientes, recetas, pedidos, detalles_pedidos).

Cargar las cuentas oficiales Ejecuta el script de la carpeta migrations: mysql -u root -p pupuseria_test < migrations/02_apply_seed_users.sql Esto crea los tres usuarios listados arriba.

Verificar la configuración de conexión Revisa config/database.php y confirma que el nombre de la base de datos, usuario y contraseña coincidan con tu instalación local de MySQL.

Abrir el sistema Ve a: http://localhost/erp_pupuseria/ Esto te llevará automáticamente a la pantalla de login. Ingresa con cualquiera de las cuentas oficiales.

🔑 Notas
El acceso a los usuarios está protegido: solo las cuentas registradas en la base de datos (con su contraseña cifrada correspondiente) pueden iniciar sesión.
Para cerrar sesión correctamente, siempre usa el botón "Log Out" del menú lateral.

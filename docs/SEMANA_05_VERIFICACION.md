# Semana 05 — Correspondencia entre bitácora y sistema de gimnasio

Actualización del código: 09/11/2026. Se toma como referencia la bitácora EFSRT: 10, 12 y 14/10/2026. Esta ficha describe el código disponible y las pruebas pendientes en el equipo del usuario; no sustituye las evidencias reales del periodo registrado.

| Actividad documentada | Aplicación técnica en este repositorio | Evidencia comprobable |
| --- | --- | --- |
| Mejorar reservas y su relación con usuarios y socios | \`Reserva.php\`, \`ReservasController.php\`, \`reservas/index.php\`. Se guarda socio_id, usuario_id, fecha, hora, tipo y observaciones. | Tabla \`reservas\` en MySQL y vista Reservas |
| Revisar los estados de reserva | Estados pendiente, confirmada, completada y cancelada; edición, borrado y verificación de colisión de horario. | Cambiar estado y comprobar que se conserva tras recargar |
| Completar productos con nombre, precio y stock | CRUD existente de \`Producto.php\`, mejorado con validaciones de stock para impedir salidas mayores al disponible. | Vista Inventario y tabla \`productos\` |
| Guardar y mostrar imagen del producto | Carga JPG/PNG/WEBP (hasta 5 MB), imágenes locales, selector de fotografías ya existentes, vista previa y aparición en inventario y POS. | \`public/img/productos/\` y \`productos.foto\` |
| Comparar pagos y socios con la BD | Módulos \`Socios\`, \`Suscripciones\`, \`Caja\` y \`Punto de Venta\` ya existían. | Requiere prueba con MySQL real y capturas de pagos/socios |

## Por qué antes no se veían fotografías

El respaldo \`bk_basededatos.sql\` trae los diez productos de prueba con \`foto = NULL\`, aunque la carpeta \`public/img/productos/\` contiene archivos. La aplicación no puede adivinar qué fotografía corresponde a cada producto; el administrador debe seleccionarla una vez desde Inventario > Editar producto > "O seleccionar una imagen ya guardada". A partir de entonces queda vinculada en la BD y aparece también en Punto de Venta.

## Pantalla agregada: Verificación Semana 5

En el menú del administrador, abre **Verificación Semana 5** (`/revision/index`). Muestra:

- Estado de reservas y enlace para gestionarlas (pendiente/confirmada/completada/cancelada).
- Productos sin imagen vinculada o cuyo archivo local desapareció.
- Ventas con nombres del socio o cliente general, medio de pago y comparación contra detalle y descuento.
- Diferencias entre `cajas.total_ventas` y la suma de `ventas.total`.
- Socios con cantidad de suscripciones y compras, calculadas por separado para evitar duplicados.

La lectura es de solo consulta y **no ajusta importes ni cambia datos automáticamente**. La conciliación no equivale a prueba de pagos de suscripciones: no hay tabla de cobros de suscripciones en el esquema actual.

Se reforzó además `app/models/Venta.php` para que una venta solo se confirme con caja abierta, producto activo y stock suficiente. El total es verificado contra el precio actual; los detalles, stock y total de caja se guardan juntos en una transacción. Si algo falla, se revierte.

## Verificación en XAMPP / Laragon

1. En la copia de este repositorio, ejecutar \`git pull origin main\` y tener Apache y MySQL iniciados.
2. Confirmar \`gym_system\` y la conexión en \`app/config/Database.php\`.
3. Abrir el dashboard con rol administrador; entrar a **Inventario**.
4. Editar un producto con fotografía en la carpeta local; seleccionar la fotografía, comprobar la vista previa y pulsar Guardar.
5. Recargar Inventario, confirmar imagen y stock; acceder a Punto de Venta (si requiere caja abierta, abrirla).
6. Crear una reserva para un socio; editar su estado a Confirmada, comprobar que se guardó y que un horario ocupado se rechaza.
7. Verificar un ajuste de salida mayor al stock y comprobar que se rechaza sin dejar negativos.
8. Contrastar socios y pagos con la base de datos real y conservar capturas auténticas para las evidencias institucionales.

La tabla de reservas se crea al entrar al módulo desde una cuenta autorizada con permisos de creación de tablas. También está disponible \`database/migrations/2026_11_09_reservas.sql\` para ejecutarla manualmente si el usuario de MySQL no tiene esos permisos.

## Comprobaciones automáticas

- Acción CI: \`.github/workflows/semana05-php-lint.yml\`.
- Pruebas de integración con MySQL temporal: \`tests/semana05_smoke.php\`.
- Ninguna prueba CI valida automáticamente la instalación o base de datos privada del equipo ni genera capturas reales de la aplicación.

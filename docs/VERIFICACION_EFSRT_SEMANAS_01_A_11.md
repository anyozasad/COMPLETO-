# Mallqui Gym — Verificación de las 11 semanas EFSRT

Fecha de análisis de código: 09/11/2026. Referencias: Bitácora EFSRT de 11 semanas y Registro_EFSRT_Tarde_4H.

> IMPORTANTE: esta matriz compara **funciones de código del repositorio PHP** con actividades documentadas. No certifica el funcionamiento del proyecto Flutter no accesible, ni acredita asistencia, firmas, capturas o trabajo realizado en fechas anteriores. Las actividades del 11 al 21 de noviembre todavía aparecen programadas en el documento.

## Arquitectura evaluada

- **Área usuario:** `/portal/index`, `/portal/login`, `/portal/registrar`; catálogos y reservas.
- **Dashboard PHP:** `/auth/index`, `/home/index`, y módulos de Socios, Reservas, Pagos, Inventario, Asistencia, Progreso, Rutinas, Entrenador, Reportes, Auditoría.
- **API:** `/api/salud`, `/api/productos`, `/api/login`, `/api/registrar`, `/api/reservas`, `/api/reservar`, `/api/cancelar`, `/api/rutina`, `/api/entrenador`, `/api/logout`.
- **Base de datos:** `gym_system`; tablas existentes y adicionales `reservas`, `clientes_portal`, `programas_rutina`, `pagos_suscripciones`, `tokens_api`, `entrenador_fotos`.
- **Recursos locales:** `public/img`, `public/vendor`. Los recursos del entrenador y de productos deben vincularse a la base de datos.

## Matriz de las 55 actividades

### Semana 01 — Requerimientos, arquitectura y base de datos

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 1 | Revisar necesidades del gimnasio y funciones principales | Documentar requisitos funcionales de usuario y administración | Documentado; validación empresarial pendiente |
| 2 | Organizar requisitos de usuario y administrador | /portal, /auth, /home, /inventario, /reservas, /pagos | Aplicado en PHP; Flutter no verificado |
| 3 | Separar usuario, dashboard, API y BD | app/controllers/PortalController.php; ApiController.php; app/controllers; MySQL gym_system | Aplicado en PHP; integración Flutter pendiente |
| 4 | Definir datos de socios, reservas, pagos, productos y rutinas | schema.sql; app/models/Reserva.php; PagoSuscripcion.php; RutinaRecomendada.php | Aplicado |
| 5 | Crear BD inicial y relaciones | schema.sql; database/migrations/2026_11_09_reservas.sql | Aplicado en código; evidencia de BD local pendiente |

### Semana 02 — Página del usuario

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 6 | Configurar frontend y rutas | app/controllers/PortalController.php; app/views/portal/index.php | Aplicado como portal PHP; Flutter aparte |
| 7 | Armar menú de secciones | Menú público: Planes y rutinas, Productos, Reservas, ingresar y registrar | Aplicado |
| 8 | Crear portada con mensaje y botón de reservar | Vista /portal/index, enlace a reserva | Aplicado; falta foto principal real |
| 9 | Organizar secciones entrenamiento y gimnasio | Vista portal: entrenador, rutina, productos y reservas | Parcial: imagen de portada pendiente |
| 10 | Probar tamaños y corregir responsive | CSS responsive en app/views/portal/index.php | Implementado; capturas de dispositivos pendientes |

### Semana 03 — Login y registro

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 11 | Diseñar inicio de sesión coherente | app/views/auth/login.php; app/views/portal/index.php | Disponible; igualdad exacta del diseño no verificada |
| 12 | Formulario de registro de cuentas | app/controllers/PortalController.php; app/models/Portal.php | Aplicado |
| 13 | Conectar login y registro a backend y BD | clientes_portal vinculado a socios en gym_system | Probado con MySQL de prueba |
| 14 | Validaciones para datos incorrectos o vacíos | DNI, correo, clave, duplicados, CSRF y sesión | Aplicado y probado |
| 15 | Probar acceso y corregir errores | tests/portal_smoke.php; AuthController.php | API/portal probados; prueba manual en equipo pendiente |

### Semana 04 — Dashboard y CRUD

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 16 | Armar dashboard administrativo | app/views/home/index.php; HomeController.php | Existente |
| 17 | Organizar socios, reservas, pagos, productos, entrenador, reportes | app/views/inc/navbar.php; módulos bajo app/controllers | Aplicado |
| 18 | Registrar y listar socios | app/controllers/SociosController.php; app/models/Socio.php | Existente; verificar en BD local |
| 19 | Editar/eliminar socios y reservas | SociosController.php (desactivación); ReservasController.php (edición/eliminación) | Reservas aplicadas; socios con baja lógica |
| 20 | Verificar cambios persistidos en BD | tests/semana05_smoke.php; tests/portal_smoke.php | Probado en MySQL temporal; BD de producción pendiente |

### Semana 05 — Reservas, productos y pagos

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 21 | Relacionar reservas con usuarios y socios | app/models/Reserva.php; Portal.php; ApiController.php | Aplicado y probado |
| 22 | Gestionar estados de reserva | pendiente, confirmada, completada, cancelada en reservas/index.php | Aplicado |
| 23 | Completar productos con nombre, precio y stock | app/models/Producto.php; inventario/productos.php | Aplicado; validación stock probada |
| 24 | Guardar y mostrar fotografía del producto | InventarioController.php; public/img/productos; vista previa | Aplicado; asociar fotos faltantes en BD real |
| 25 | Revisar pagos y socios frente a BD | RevisionSemana05.php; PagoSuscripcion.php; /revision/index | Aplicado en MySQL de prueba |

### Semana 06 — Asistencia y rutinas

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 26 | Agregar módulo de asistencia | AsistenciaController.php; Asistencia.php | Existente y validado para evitar duplicados |
| 27 | Hacer seguimiento del progreso de socios | ProgresoController.php; Progreso.php; vistas/progreso/ver.php | Existente |
| 28 | Organizar rutinas por nivel y tipo | RutinaRecomendada.php; RutinasController.php; programas_rutina | Aplicado y vinculado al portal |
| 29 | Mostrar entrenador principal | EntrenadorController.php; Entrenador.php; fotos locales | Aplicado; falta subir foto real |
| 30 | Probar módulos y eliminar repeticiones | tests/portal_smoke.php; tests/semana05_smoke.php | Pruebas de modelos; recorrido manual pendiente |

### Semana 07 — Diseño y navegación

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 31 | Unificar estilo de módulos del dashboard | CSS gym-theme.css compartido; vistas existentes | Parcial: revisión visual final pendiente |
| 32 | Ajustar tarjetas, botones y espacios | Diseño anterior conservado; nuevos módulos reutilizan clases CSS | Parcial: verificar en navegador |
| 33 | Eliminar elementos repetidos/innecesarios | /revision/index detecta posibles duplicados de BD | Parcial: revisión de interfaz pendiente |
| 34 | Adaptar login al diseño principal | app/views/auth/login.php; protección CSRF sin mover diseño | Parcial: comparación con referencia pendiente |
| 35 | Corregir botones y navegación | Sidebar en navbar.php; módulos nuevos con rutas | Aplicado a módulos nuevos; prueba integral de clics pendiente |

### Semana 08 — Imágenes y recursos locales

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 36 | Revisar calidad y nitidez de imágenes | Carpetas public/img y panel /entrenador/index | Parcial: inspección de fotos reales pendiente |
| 37 | Guardar imágenes y recursos locales | public/vendor; public/img/productos; public/img/entrenadores | Recursos JS/CSS aplicados; completar fotos faltantes |
| 38 | Actualizar imágenes entrenamiento, entrenador y productos | Gestor de foto del entrenador, productos con imágenes locales | Parcial: archivos reales de entrenamiento pendientes |
| 39 | Actualizar imagen principal del gimnasio | /portal/index tiene portada, no contiene foto 4K real | Pendiente: archivo de portada proporcionado por empresa |
| 40 | Probar usuario, dashboard y BD | pruebas GitHub Actions con MySQL | Pruebas de backend aplicadas; verificación visual real pendiente |

### Semana 09 — Integración, duplicados y alertas

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 41 | Revisar reservas entre usuario y dashboard | Reserva.php compartida; PortalController.php; ApiController.php | PHP probado; Flutter no verificado |
| 42 | Comparar socios, productos, pagos y BD | /revision/index; reportes de caja y detalle | Aplicado |
| 43 | Corregir registros repetidos y no actualizados | posiblesDuplicados() y reglas de stock y asistencia | Detección aplicada; corrección manual supervisada pendiente |
| 44 | Probar alerta de vencimiento | Dashboard::obtenerVencimientosCercanos() y banner home | Implementado; captura local pendiente |
| 45 | Revisar asistencia, seguimiento y rutinas | Asistencia.php; Progreso.php; RutinaRecomendada.php | Modelos preparados; revisión operativa pendiente |

### Semana 10 — Pagos, comprobante y seguridad

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 46 | Revisar flujo completo de pagos | PagoSuscripcion.php; PagosController.php; Venta.php | Aplicado y probado con MySQL |
| 47 | Preparar boleta electrónica | TicketController.php; Pagos/comprobante.php (RECIBO INTERNO) | Pendiente CPE SUNAT: falta emisor autorizado, firma y envío |
| 48 | Probar recursos e imágenes sin Internet | public/vendor y fotos almacenadas en public/img | Bibliotecas locales aplicadas; prueba desconectado pendiente |
| 49 | Revisar permisos de usuario y administrador | Auth.php; rol en Pagos, Inventario y API TokenApi | Aplicado en módulos claves; auditoría completa pendiente |
| 50 | Pruebas y corrección de errores finales | CI PHP/MySQL y revisión EFSRT | Pruebas automáticas aplicadas; pruebas de usuario pendientes |

### Semana 11 — Cierre técnico y evidencia

| N.º | Actividad de la bitácora | Código o evidencia técnica | Estado de verificación |
|---:|---|---|---|
| 51 | Probar login, registro y navegación del usuario | AuthController.php; PortalController.php; tests/semana11_http.py; tests/semana11_browser.mjs | Implementado y probado en HTTP/Chromium CI; instalación final pendiente |
| 52 | Probar módulos principales del dashboard | Rutas de 11 módulos; tests/semana11_http.py; capturas de navegador | Navegación de dashboard comprobada en CI; prueba manual en equipo pendiente |
| 53 | Revisar duplicados y datos faltantes de BD | CierreSistema.php; /cierre/index; tests/semana11_integridad.php | Detección en MySQL implementada; registros de empresa no se borran automáticamente |
| 54 | Corregir últimos detalles visuales | CSS local; navbar activo en subcarpetas; tablas en español sin CDN; pruebas Chromium | Mejoras y capturas de CI; comparar diseño final en equipo |
| 55 | Organizar evidencias y respaldo | /cierre/exportar; ZIP y PNG de CI; scripts/respaldo_efsrt.ps1; docs/SEMANA_11_CIERRE_FUNCIONAL.md | Evidencia técnica generada; respaldo SQL real y firmas pendientes en instalación |

Consulta también [Semana 11: cierre funcional y pruebas](SEMANA_11_CIERRE_FUNCIONAL.md) y el [workflow de navegación y capturas](https://github.com/anyozasad/COMPLETO-/actions/workflows/semana11-end-to-end.yml).

## Evidencias que debe verificar el usuario en su equipo

1. Iniciar Apache y MySQL. Antes de migrar o restaurar una BD con información real, hacer respaldo.
2. Verificar rutas `http://localhost/COMPLETO-/public/portal/index` y `http://localhost/COMPLETO-/public/auth/index` (adaptar ruta al nombre del directorio instalado).
3. Desde portal, registrar una cuenta de prueba nueva, acceder, crear reserva y comprobar su estado en Dashboard > Reservas.
4. Cargar una fotografía real en Inventario, guardarla, recargar y comprobar que aparece en la página pública y en Punto de Venta.
5. Crear/asignar rutina y comprobar que el socio ve la indicación del día; revisar que solo aparezca el entrenador real activo con su fotografía guardada.
6. Registrar pago de un plan con caja abierta y comprobar importe, saldo, recibo interno y caja. Para documentos fiscales válidos se requiere integración con SUNAT.
7. Comprobar asistencia con suscripción vigente y rechazo de segundo ingreso el mismo día; comprobar rechazo con membresía vencida.
8. Apagar temporalmente Internet (pero conservar Apache/MySQL locales) y comprobar que imágenes y bibliotecas locales se cargan sin CDN.
9. Revisar la auditoría EFSRT y documentar duplicados/archivos faltantes; corregir datos reales solo después de validarlos.
10. Ejecutar `scripts/respaldo_efsrt.ps1` en PowerShell para crear un ZIP con código, fotos y SQL, y comprobar que el ZIP abre correctamente.
11. Obtener capturas genuinas de cada pantalla y validación. No incluir pruebas o firmas inventadas.

## Pruebas automáticas

- `tests/semana05_smoke.php`: tablas relacionadas, reservas, fotos de producto, stock, cobros de plan, conciliación de caja y asistencia.
- `tests/portal_smoke.php`: cuenta del socio, login, reservas compartidas, cancelación, permisos por cliente, API-token y rutina por nivel.
- `.github/workflows/semana05-php-lint.yml`: sintaxis PHP y base MySQL efímera.
- `.github/workflows/recursos-locales.yml`: descarga recursos CSS/JS/fonts localmente sin modificar composición visual.

## Pendientes importantes para declarar '100% completo'

- **Flutter real:** falta abrir y ejecutar el código Flutter exacto que usa el profesor. Tener API no demuestra que la app Flutter la consuma.
- **Fotografías 4K:** la carpeta local puede estar configurada, pero fotos reales de portada, entrenamiento y entrenador se deben aportar/validar y comprobar visualmente.
- **Boleta electrónica peruana:** un PDF/recibo interno NO equivale a CPE SUNAT emitido. La emisión formal requiere proveedor/equipo autorizado, firma, numeración, transmisión y respuesta aceptada.
- **Pruebas end-to-end:** los tests de MySQL no sustituyen las pruebas reales del frontend ni evidencia en tu instalación.
- **Horas y firmas:** la bitácora establece futuras actividades y supervisor; no se deben marcar como validadas sin evidencia y supervisión.

## Precaución con bases existentes

El archivo `bk_basededatos.sql` contiene datos de prueba y sentencias que pueden borrar datos. **No lo importes sobre la BD real**. El respaldo es obligatorio antes de cambiar la estructura o restaurar copias.

# Mallqui Gym — Semana 11: cierre funcional de las cinco actividades

Fecha de trabajo técnico: 9 de noviembre de 2026. En la bitácora, las actividades finales están programadas para el 20 y 21 de noviembre. No se deben inventar firmas o asistencia anticipada.

## 1. Probar login, registro y navegación del usuario

- Rutas: /portal/registrar, /portal/login, /portal/index y /auth/index.
- Tests: tests/semana11_http.py crea un socio de prueba en una BD temporal, rechaza contraseña errónea, inicia sesión, solicita una reserva y verifica la navegación y la protección del panel.
- Capturas: tests/semana11_browser.mjs abre páginas reales con Chromium y guarda imágenes PNG de la interfaz.
- Estado: implementado y probado en CI; pendiente de validación sobre el equipo local del alumno.

## 2. Probar módulos principales del dashboard

- La prueba HTTP inicia sesión como administrador y entra en Socios, Reservas, Inventario, Pagos, Asistencia, Rutinas, Entrenador, Reportes, Usuarios, Verificación EFSRT y Cierre Semana 11.
- Una reserva creada desde portal aparece en el dashboard porque utilizan la misma tabla MySQL.
- Estado: prueba HTTP y bases de prueba realizadas; falta el recorrido manual del profesor en el entorno instalado y verificar Flutter.

## 3. Revisar duplicados y datos incompletos

- Módulo Cierre Semana 11: /cierre/index.
- Modelo CierreSistema.php revisa socios, usuarios, códigos y stock, reservas coincidentes, pagos, fechas de suscripción, ventas sin detalle, nombres vacíos e imágenes faltantes.
- Test semana11_integridad.php introduce errores ficticios en MySQL aislado, verifica que se detectan y limpia los registros de prueba.
- La herramienta NO elimina datos reales automáticamente ni inventa que la base de producción está perfecta.

## 4. Corregir últimos detalles de interfaz

- Corrección de la navegación del sidebar al instalar el sistema en subcarpetas.
- Traducción de tablas al español sin solicitar el JSON desde Internet.
- Bibliotecas CSS, JavaScript, gráficos e iconos guardadas en public/vendor; fotos en public/img.
- Acceso a Configuración limitado al rol administrador, con control CSRF y carga segura de imágenes.
- Capturas de Chromium en pantalla de escritorio y móvil y comprobación de errores JavaScript e imágenes rotas.
- Aún se debe cotejar visualmente la instalación real y las fotografías que subió el gimnasio.

## 5. Organizar evidencias y respaldo

- En Cierre Semana 11, botón Descargar diagnóstico: documento JSON con fecha y conteos reales de MySQL, sin contraseñas de los socios.
- GitHub Actions, workflow Semana 11 - Navegación, BD y evidencias reales, publica como artefacto: registros HTTP, resultados SQL, capturas PNG de Chromium y ZIP con código para pruebas.
- El ZIP de CI contiene datos ficticios o esquema de pruebas, no la base de datos real de la empresa.
- Para respaldar realmente tu instalación Windows, usa scripts/respaldo_efsrt.ps1 con XAMPP/MySQL: genera el ZIP local con SQL, código e imágenes.
- Guarda las capturas auténticas y recoge la firma del supervisor cuando corresponda.

## Verificación rápida para el profesor

1. Antes de cualquier cambio, respalda la BD actual; no importes bk_basededatos.sql sobre una BD con información real.
2. Actualiza el repositorio con: git pull origin main.
3. Inicia Apache y MySQL. Si la base es antigua y ya hiciste respaldo, ejecuta: php app/config/migrate.php.
4. Entra al panel, abre Cierre Semana 11 y descarga el diagnóstico.
5. Desde portal registra una cuenta nueva, inicia sesión y crea una reserva. Comprueba su aparición en Reservas del dashboard.
6. Revisa productos e imágenes, historial de pagos, control de asistencia, rutina del socio y roles.
7. Desconecta Internet externo conservando Apache/MySQL local y verifica la interfaz y las imágenes.
8. Crea un ZIP de respaldo con scripts/respaldo_efsrt.ps1 y confirma su contenido.
9. Adjunta las capturas reales, la evidencia técnica y las firmas verdaderas.

## Límites de la evidencia

Las pruebas se ejecutan sobre el PHP accesible en GitHub: anyozasad/COMPLETO-. No prueban el proyecto Flutter exacto, no certifican fotos 4K faltantes y no convierten un recibo interno en boleta fiscal SUNAT. Tampoco acreditan que las actividades futuras de la bitácora ya hayan ocurrido.

[Ver ejecución y artefactos](https://github.com/anyozasad/COMPLETO-/actions/workflows/semana11-end-to-end.yml)

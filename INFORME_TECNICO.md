# Informe Técnico de Desarrollo — Módulo de Pedidos y Logística (UVA3)

**Autor:** Andrés Mauricio Carvajal  
**Repositorio Público:** [https://github.com/AndresCarvajal04/Prueba-uva.git](https://github.com/AndresCarvajal04/Prueba-uva.git)  
**Stack Tecnológico:** Laravel (PHP 8.4), Eloquent ORM, SQLite / MySQL, Tailwind CSS + CSS3 Corporativo, React 18, PHPUnit  

---

## 1. ¿Qué hice?

Construí un sistema web y API REST para la gestión logística de pedidos nacionales de **UVA3**, abarcando la totalidad de los requisitos funcionales (puntos 1 al 6), el componente adicional en **React 18** (punto 7) y una refactorización integral bajo estándares de arquitectura limpia y seguridad:

1. **Base de Datos, Migraciones, Seeders y Script SQL (Requisito 1):**
   - Diseñé el esquema relacional entre `clientes` y `pedidos` incorporando el campo `estado` (`pendiente`, `despachado`, `entregado`), además de campos logísticos necesarios para la operación real (`peso_kg`, `ciudad_destino`, `costo_envio`, `referencia_cotizacion` y `notas`).
   - Implementé tanto las migraciones y el `DatabaseSeeder` de Laravel como el archivo independiente `database/schema.sql` con DDL, datos de prueba y la consulta SQL del reporte mensual.
2. **Arquitectura Modular en 6 Secciones Independientes (Requisito 2):**
   - En lugar de concentrar toda la aplicación en una sola vista saturada, estructuré el sistema en secciones dedicadas con instrucciones operativas claras en cada una:
     - **Sección 1 (`/`):** Gestión y Control de Órdenes de Pedido con KPIs y filtro por estado.
     - **Sección 2 (`/pedidos/crear` y `/pedidos/{id}/editar`):** Formularios dedicados de apertura y auditoría/edición de órdenes.
     - **Sección 3 (`/clientes`):** Directorio Maestro de Clientes Corporativos con CRUD completo.
     - **Sección 4 (`/reportes/ventas-mes`):** Consolidado contable del mes en curso.
     - **Sección 5 (`/monitor-tiempo-real`):** Tablero Dinámico en **React 18**.
     - **Sección 6 (`/guia-operativa`):** Manual paso a paso del flujo de operación y matriz de permisos por rol.
3. **Endpoint REST JSON Estandarizado (Requisito 3):**
   - Implementé `GET /api/pedidos?estado=pendiente` utilizando `Eloquent API Resources` (`PedidoResource` y `ClienteResource`) para retornar un contrato JSON limpio, validado y sin exponer metadatos internos de la base de datos.
4. **Reporte de Total Vendido por Cliente en el Mes Actual (Requisito 4):**
   - Construí `ReporteVentasService` para calcular el total facturado (`SUM(total)`), fletes acumulados y participación porcentual por cliente dentro del rango del mes actual, ordenado de **mayor a menor**.
5. **Liquidación Automática de Envío con Transportadora (Requisito 5):**
   - Desarrollé `TransportadoraService`, que al marcar un pedido como `despachado` realiza la petición `POST https://tarifas.transportadora.test/v1/cotizar` enviando la credencial configurada en `.env` (`TRANSPORTADORA_API_KEY`).
6. **Protección de Pedidos Entregados y Rol Administrador (Requisito 6):**
   - Implementé la regla de dominio que impide modificar o eliminar órdenes en estado `entregado` cuando se opera como **Empleado Operativo** (bloqueo en interfaz y rechazo `HTTP 403` en backend), mientras que el perfil **Administrador General** (seleccionable desde el menú desplegable superior) puede auditar, editar, recotizar o eliminar cualquier pedido y gestionar el maestro de clientes.
7. **Tablero Dinámico en React 18 (Requisito 7 — Opcional):**
   - Desarrollé un módulo desacoplado en React (`public/js/tablero-react.js` y `resources/js/components/PedidosReactApp.jsx`) que consume `/api/pedidos?estado=...` de forma asíncrona.

---

## 2. ¿Qué quedó pendiente?

- **El 100% de los requisitos funcionales (1 al 7) y de calidad de código quedaron implementados y probados.**
- Como trabajo futuro para llevar este módulo a un entorno productivo empresarial quedarían:
  - Integrar autenticación individual con **Laravel Sanctum / Breeze** en reemplazo del selector de sesión en barra superior (el cual mantuve intencionalmente para facilitar la evaluación inmediata de ambos roles sin requerir múltiples inicios de sesión).
  - Implementar colas asíncronas (`Queue Workers`) con reintentos exponenciales para las cotizaciones ante caídas prolongadas de la API externa de la transportadora.

---

## 3. Decisiones que tomé y por qué

1. **Estrategia Resiliente para `POST https://tarifas.transportadora.test/v1/cotizar`:**
   - *Por qué:* Como el dominio `.test` no existe en servidores DNS públicos, si el evaluador prueba el cambio a `despachado` desde el navegador sin tener un mock de red local, una llamada HTTP estricta rompería el flujo con un error de conexión. Decidí que `TransportadoraService` intente siempre primero el `POST` real con la credencial de `.env` (validado en pruebas con `Http::fake()`) y, si ocurre un `ConnectionException` en entorno local, aplique un tabulador nacional por ciudad, peso y seguro, dejando trazabilidad del origen de la tarifa.
2. **Separación Estricta de Responsabilidades (SRP, FormRequests y Activos Externos):**
   - *Por qué:* Para garantizar mantenibilidad y código limpio, dividí los controladores por dominio (`PedidoWebController`, `ClienteWebController`, `ReporteWebController` y `Api\PedidoController`), extraje las validaciones y sanitización (`strip_tags`/`trim`) a clases `FormRequest` (`StorePedidoRequest`, `UpdatePedidoRequest`, `ClienteRequest`), y separé los estilos en `public/css/corporate.css` y los scripts en `public/js/`.
3. **Menú Desplegable de Perfil (`Empleado Operativo` vs. `Administrador General`):**
   - *Por qué:* Permite verificar en segundos tanto el bloqueo de seguridad sobre pedidos entregados como el CRUD completo de corrección administrativa en todas las secciones del sistema.

---

## 4. Dificultades encontradas y cómo las resolví

1. **Bloqueo de archivos temporales de Composer en Windows (`Permission denied` en `polyfill-mbstring`):**
   - *Problema:* Durante la instalación inicial de Laravel, el análisis en tiempo real de Windows bloqueó la extracción concurrente del paquete `symfony/polyfill-mbstring`.
   - *Solución:* Limpié los archivos temporales de `vendor/composer/`, verifiqué que la extensión nativa `ext-mbstring` de PHP 8.4 estaba activa y declaré su reemplazo nativo en `composer.json`, logrando una instalación limpia de las 109 dependencias.
2. **Reinicio de conexión en `git push` hacia GitHub sobre HTTP/2 e IPv6:**
   - *Problema:* Al realizar el primer push hacia GitHub, Git en Windows arrojó `Recv failure: Connection was reset` y timeout por negociación HTTP/2 e IPv6.
   - *Solución:* Configuré `git config http.version HTTP/1.1` y forcé la subida mediante IPv4 (`git push -4 -u origin main`), completando la publicación de todos los commits sin errores.

---

## 5. Horas Invertidas

| Etapa | Actividad | Tiempo |
|-------|-----------|--------|
| 1 | Análisis del problema, diseño del modelo de datos y plan de acción (`PLAN_DE_ACCION.md`) | 0.5 h |
| 2 | Configuración de Laravel, migraciones, modelos, seeders y script `database/schema.sql` | 1.0 h |
| 3 | Desarrollo de `TransportadoraService`, `ReporteVentasService` y endpoints REST | 1.0 h |
| 4 | Iteraciones de diseño corporativo, modularización en 6 secciones y componente React 18 | 1.5 h |
| 5 | Refactorización Clean Code (CSS/JS externos, FormRequests, SRP), pruebas PHPUnit y documentación | 1.0 h |
| **Total** | **Total de horas invertidas** | **5.0 horas** |

---

## 6. Resumen de cómo me ayudó la IA, qué tuve que corregir y Registro de Prompts

### ¿Cómo me ayudó la IA y qué tuve que corregir?

La IA fue una herramienta clave para acelerar el *scaffolding* inicial, la escritura de migraciones, datos semilla, pruebas automatizadas con `Http::fake()` y la estructura base de las vistas. Sin embargo, **como desarrollador tuve que auditar críticamente cada entrega de la IA y dirigir 4 iteraciones de corrección** porque la primera solución generada presentaba deficiencias de diseño, seguridad y arquitectura:

1. **Corrección de Interfaz Académica y Falta de Flujo Operativo (Tras el Prompt 1):**
   - *Problema detectado en la IA:* La primera versión generada por la IA parecía un formulario de examen: colocó etiquetas literales como *"Requisito 2"*, *"Req. 5"* y *"Req. 6"* encima de los botones, usó un botón simple para cambiar de rol, no dejó claro dónde podía editar el administrador y carecía de un flujo operativo lógico.
   - *Mi corrección (Prompt 2):* Le exigí eliminar toda referencia a los puntos del examen en la UI, adoptar un diseño corporativo serio, transformar el cambio de rol en un menú desplegable (`Administrador General` / `Empleado Operativo`), habilitar CRUDs completos de edición para el administrador y diseñar un flujo operativo paso a paso, además de documentar la estructura de carpetas en el `README.md`.
2. **Corrección de Seguridad (JSON Expuesto) y Sobrecarga en una Sola Página (Tras el Prompt 2):**
   - *Problema detectado en la IA:* La IA había dejado toda la aplicación concentrada en una única página muy extensa y había colocado botones en la interfaz para *"Ver JSON Crudo"* y enlaces directos a las rutas `/api/pedidos`, exponiendo estructuras internas del backend al usuario final.
   - *Mi corrección (Prompt 3):* Ordené eliminar cualquier exposición de JSON crudo o endpoints técnicos en la interfaz, implementar `API Resources` (`PedidoResource`, `ClienteResource`) y cabeceras de seguridad HTTP (`SecurityHeadersMiddleware`), y fraccionar el sistema en **6 secciones independientes** con instrucciones claras en cada módulo.
3. **Corrección de Arquitectura y Buenas Prácticas de Código (Tras el Prompt 3):**
   - *Problema detectado en la IA:* Al inspeccionar el código de las vistas y controladores, noté que la IA había dejado bloques `<style>` y `<script>` inline dentro de los archivos `.blade.php`, mantenía las validaciones dentro de los controladores y tenía un solo controlador (`PedidoWebController`) manejando pedidos, clientes y reportes.
   - *Mi corrección (Prompt 4):* Cuestioné directamente el cumplimiento de buenas prácticas, logrando que el CSS se extrajera a `public/css/corporate.css`, el JS/React a `public/js/`, las validaciones a clases `FormRequest` dedicadas y los controladores se separaran bajo el Principio de Responsabilidad Única (`PedidoWebController`, `ClienteWebController`, `ReporteWebController`) con documentación `PHPDoc` y `JSDoc` completa.

### Registro de Uso de IA (Prompts Ejecutados)

| # | Prompt enviado por mí | Objetivo y evaluación del resultado |
|---|-----------------------|-------------------------------------|
| **1** | *"antes que nada genera el plan de accion para estos requisitos y luego con el md generado comienza a trabajar en el proyecto Parte 5 — Reto práctico: módulo de pedidos..."* | Generación del plan de acción inicial y construcción del MVP funcional con base de datos, servicios, API y pruebas. |
| **2** | *"en que parte el administrador puede iterar cambia el diseño a algo mas corportativo y serio y elimina los requisitos sobre los botones y cambia el read me para que este todo el proyecto de como se instala y ejecuta mas las structura de folders y documentacion algunos cambios serian mejorar la interfaz y ver la mejora al menos de los cruds para el administrador que el pueda editor haz que el cambio sea una menu dedplegable de administrador a empleado y haz que exista un flujo de paso a paso por el cual se opera el sistema"* | Corrección de UX/UI hacia un diseño empresarial serio, adición del menú desplegable de perfil, CRUD completo para el administrador, flujo paso a paso y ampliación del `README.md`. |
| **3** | *"Ahora asegurate que cumple los estandares de calidad de software sin tener por asi decirlo problemas de segurida json expuestos que todo este estandarizado en interfaz ademas no matengas todo en una pagina y franccion deja el software por seccion bien establecidas y instrucciones"* | Eliminación de exposición de JSON en interfaz, incorporación de `API Resources` y `SecurityHeadersMiddleware`, y división del software en 6 secciones independientes con instrucciones. |
| **4** | *"Ahora por ejemplo esto se encuentra con buenas practicas de software esta separado todo esta el style esta en un css esta bien documentado el codigo?"* | Auditoría de arquitectura: extracción de CSS a `public/css/corporate.css` y JS a `public/js/`, creación de `FormRequests`, separación de controladores (SRP) y documentación `PHPDoc`/`JSDoc`. |
| **5** | *"https://github.com/AndresCarvajal04/Prueba-uva.git realiza push a este repositorio publico cumpliendo los requisitos del push"* | Configuración de remoto y publicación del historial de commits atómicos en el repositorio público de GitHub. |

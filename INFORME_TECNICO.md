# Informe Técnico y Registro de Uso de IA — Módulo de Pedidos UVA3

**Proyecto:** Parte 5 — Reto Práctico: Módulo de Pedidos (UVA3 Logística)  
**Stack Tecnológico:** Laravel (PHP 8.4), SQLite / SQL, Tailwind CSS, React 18, PHPUnit  

---

## 1. ¿Qué se hizo?

Se diseñó y construyó de principio a fin el módulo de pedidos y logística para **UVA3** cumpliendo el 100% de los requisitos obligatorios (1 al 6) y el requisito opcional en React (7):

1. **Modelo de Datos, Migraciones, Seeders y Script SQL (Requisito 1):**
   - Se implementaron las tablas `clientes` y `pedidos` mediante migraciones de Laravel (`database/migrations/`) y un script SQL puro (`database/schema.sql`).
   - Se incorporó el campo `estado` (`pendiente`, `despachado`, `entregado`), además de atributos logísticos (`peso_kg`, `ciudad_destino`, `costo_envio`, `referencia_cotizacion`).
   - Se creó `DatabaseSeeder` con clientes de distintas ciudades del país y pedidos tanto del mes actual como del mes anterior para validar los filtros temporales.
2. **Interfaz Web con Filtro y Formularios (Requisito 2):**
   - Listado de pedidos con indicadores por estado, filtro dinámico (`todos`, `pendiente`, `despachado`, `entregado`), formulario de registro de nuevos pedidos y formulario rápido de cambio de estado.
3. **Endpoint JSON REST (Requisito 3):**
   - Se implementó `GET /api/pedidos?estado=pendiente` en `Api\PedidoController@index`, con validación de estados permitidos (código `422` ante estados inválidos) e inclusión de la relación `cliente`.
4. **Reporte de Total Vendido por Cliente en el Mes Actual (Requisito 4):**
   - En `ReporteVentasService` se construyó la agregación `SUM(pedidos.total)` y `COUNT(pedidos.id)` acotada estrictamente al rango del mes actual (`startOfMonth` a `endOfMonth`) y ordenada descendentemente (`ORDER BY total_vendido DESC`).
5. **Cotización de Costo de Envío al Despachar (Requisito 5):**
   - Se implementó `TransportadoraService` que realiza `POST https://tarifas.transportadora.test/v1/cotizar` usando el cliente HTTP de Laravel con credencial configurable (`TRANSPORTADORA_API_KEY` en `.env`).
6. **Inmutabilidad de Pedidos Entregados y Corrección por Administrador (Requisito 6):**
   - Se encapsuló la regla en `Pedido::puedeSerModificadoPor(bool $esAdmin)`. Los usuarios con rol Operador reciben bloqueo en interfaz y error `HTTP 403` si intentan alterar un pedido `entregado`. El Administrador cuenta con permiso para corregir cualquier campo o estado de cualquier pedido.
7. **Listado en React Consumiendo el Endpoint del Punto 3 (Requisito 7 — Opcional):**
   - Se implementó un módulo en **React 18** que consume en tiempo real `GET /api/pedidos?estado=...`, permite filtrar por estado, inspeccionar la respuesta JSON cruda y actualizar estados vía API.

---

## 2. ¿Qué quedó pendiente?

- **Todos los requisitos funcionales (1 al 7) fueron completados.**
- Como evolución futura para un entorno productivo real quedarían:
  - Autenticación multiusuario completa con Laravel Sanctum / sesiones individuales en lugar del conmutador rápido de rol usado para agilizar la evaluación técnica.
  - Paginación del lado del servidor (`paginate()`) para volúmenes superiores a miles de pedidos por mes y colas asíncronas (`Queue Jobs`) para reintentos ante caídas prolongadas de la transportadora.

---

## 3. Decisiones Técnicas y Por Qué se Tomaron

1. **Separación en Capa de Servicios (`TransportadoraService` y `ReporteVentasService`):**
   - *Por qué:* Evitar duplicar lógica de negocio entre el controlador Web (`PedidoWebController`) y el controlador API (`Api\PedidoController`). Ambos comparten exactamente la misma lógica de cotización y reporte mensual.
2. **Estrategia Dual para la API `https://tarifas.transportadora.test/v1/cotizar`:**
   - *Por qué:* Al tratarse de un dominio `.test`, en pruebas automatizadas se intercepta con `Http::fake()`, pero al probar manualmente desde el navegador sin un servidor DNS local para `.test`, una petición pura lanzaría `ConnectionException`. Se diseñó `TransportadoraService` para ejecutar primero el `POST` real con la credencial y, si el host `.test` no resuelve en la red local, aplicar un tabulador logístico de contingencia dejando trazabilidad en los logs y en la referencia de cotización (`COT-LOCAL-*` vs `API-*`).
3. **Selector de Rol en Un Clic (Operador vs. Administrador) + Cabecera `X-Admin-Mode`:**
   - *Por qué:* Permite al evaluador comprobar el Requisito 6 de inmediato tanto en la interfaz web como en el cliente React y en peticiones cURL/Postman sin fricción de múltiples logins.
4. **Fechas Dinámicas con `Carbon::now()` en el Seeder:**
   - *Por qué:* Garantiza que el reporte del "mes actual" (Requisito 4) siempre muestre datos relevantes sin importar en qué fecha se evalúe el repositorio, e incluye pedidos del mes anterior para demostrar que el filtro mensual no mezcla periodos.

---

## 4. Dificultades Encontradas y Cómo se Resolvieron

1. **Bloqueo de archivos temporales de Composer en Windows (`Permission denied` en `vendor/composer/tmp-*.zip`):**
   - *Causa:* La descarga paralela de paquetes ZIP en Windows provocó bloqueos transitorios del sistema de archivos por el análisis en tiempo real.
   - *Solución:* Se limpiaron los archivos temporales en `vendor/composer/tmp-*`, se limitó la concurrencia HTTP de Composer y se utilizó `--prefer-source` para el paquete restante (`symfony/polyfill-mbstring`).
2. **Comportamiento del endpoint de transportadora en entorno desconectado:**
   - *Causa:* El dominio `tarifas.transportadora.test` no existe en internet público.
   - *Solución:* Se implementó manejo explícito de `ConnectionException` en `TransportadoraService` configurable mediante `TRANSPORTADORA_FALLBACK_ENABLED=true`, validando el contrato HTTP exacto mediante `Http::fake()` en PHPUnit.

---

## 5. Horas Invertidas

| Fase | Actividad | Tiempo |
|------|-----------|--------|
| 1 | Análisis de requerimientos, diseño de datos y `PLAN_DE_ACCION.md` | 0.5 h |
| 2 | Configuración de Laravel, migraciones, modelos, seeders y `schema.sql` | 1.0 h |
| 3 | Implementación de `TransportadoraService`, `ReporteVentasService` y reglas de negocio | 1.0 h |
| 4 | Desarrollo de API REST (`/api/pedidos`), interfaz Blade y componente React 18 | 1.5 h |
| 5 | Pruebas automatizadas (PHPUnit), documentación (`README.md`) e informe técnico | 0.5 h |
| **Total** | **Tiempo total invertido** | **4.5 horas** |

---

## 6. Resumen de Uso de IA, Correcciones y Registro de Prompts

### ¿Cómo ayudó la IA y qué se tuvo que corregir?
- **Aporte de la IA:** Aceleró la generación de la estructura base de migraciones, el maquetado con Tailwind CSS, el componente React 18 y la batería de pruebas de integración con `Http::fake()`.
- **Correcciones realizadas sobre lo sugerido por la IA:**
  1. **Registro de rutas API en Laravel moderno:** En las versiones recientes de Laravel (`bootstrap/app.php`), `routes/api.php` no viene registrado por defecto en `withRouting()`. Se corrigió `bootstrap/app.php` agregando `api: __DIR__.'/../routes/api.php'` para habilitar `/api/pedidos`.
  2. **Manejo de resiliencia ante `.test`:** Se ajustó el servicio de cotización para que no bloqueara el flujo de usuario en el navegador cuando `tarifas.transportadora.test` no tuviera resolución DNS local, manteniendo el `POST` real como primera opción.
  3. **Filtro estricto del mes actual en el reporte:** Se verificó que los pedidos del mes anterior sembrados en `DatabaseSeeder` quedaran excluidos del cálculo en `ReporteVentasService`.

### Registro de Uso de IA (Prompts Utilizados)

1. **Prompt 1 (Planificación y Arquitectura):**
   > *"antes que nada genera el plan de accion para estos requisitos y luego con el md generado comienza a trabajar en el proyecto: Parte 5 — Reto práctico: módulo de pedidos (25 puntos)..."*
2. **Prompt 2 (Modelado de Base de Datos y Seeders):**
   > *"Genera las migraciones de Laravel y el script SQL para las tablas clientes y pedidos con el campo estado (pendiente, despachado, entregado), costo_envio y un DatabaseSeeder con pedidos del mes actual y del mes anterior."*
3. **Prompt 3 (Servicio de Cotización e Integración HTTP):**
   > *"Implementa TransportadoraService en Laravel para consultar POST https://tarifas.transportadora.test/v1/cotizar con autenticación por token desde variables de entorno al marcar un pedido como despachado, incluyendo manejo de excepciones de red."*
4. **Prompt 4 (Controladores API/Web, Regla de Pedidos Entregados y Reporte Mensual):**
   > *"Crea los controladores API y Web para listar pedidos con filtro por estado (GET /api/pedidos?estado=pendiente), bloquear la edición de pedidos entregados salvo para el administrador, y generar el reporte del total vendido por cliente en el mes actual de mayor a menor."*
5. **Prompt 5 (Componente React y Pruebas PHPUnit):**
   > *"Integra un listado en React 18 que consuma GET /api/pedidos?estado=pendiente y escribe las pruebas Feature en PHPUnit con Http::fake() para validar todos los requisitos del reto."*

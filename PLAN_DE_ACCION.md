# Plan de Acción — Módulo de Pedidos y Logística (UVA3)

Este documento define la hoja de ruta técnica, arquitectónica y de entregables para construir el **Módulo de Pedidos (Parte 5 — Reto Práctico)** utilizando **Laravel 12 (PHP 8.4)** y **React** para el listado interactivo (requisito opcional #7).

---

## 1. Análisis de Requisitos y Alcance

| # | Requisito | Solución Técnica Propuesta |
|---|-----------|----------------------------|
| **1** | Base de datos con tablas `clientes` y `pedidos` (modelo pregunta 1.4) + campo `estado` (`pendiente`, `despachado`, `entregado`). Migraciones/SQL y datos de prueba. | Migraciones de Laravel (`clientes`, `pedidos`, `users`), `DatabaseSeeder` con clientes y pedidos distribuidos en el mes actual y meses anteriores, además de un archivo `database/schema.sql` con el DDL y DML puro. |
| **2** | Listado de pedidos con filtro por estado, y formularios para crear un pedido y cambiar su estado. | Interfaz web en Laravel (Blade + Tailwind CSS) con tabla de pedidos, filtro por estado (`pendiente`, `despachado`, `entregado`, `todos`), modal/formulario de creación de pedido y selector/botones de transición de estado. |
| **3** | Endpoint `GET /api/pedidos?estado=pendiente` que responda en JSON. | Controlador `Api\PedidoController@index` en `routes/api.php` que filtra por el query param `estado`, incluye la relación `cliente` y retorna una respuesta JSON estructurada con código HTTP 200 (y 422 si el estado enviado no es válido). |
| **4** | Reporte con el total vendido por cliente en el mes actual, de mayor a menor. | Consulta agregada (`SUM(pedidos.total)`, `COUNT(pedidos.id)`, `SUM(pedidos.costo_envio)`) filtrada por el mes y año en curso (`fecha_pedido`), agrupada por cliente y ordenada de forma descendente (`ORDER BY total_vendido DESC`). Disponible en vista web y endpoint JSON. |
| **5** | Al marcar un pedido como `despachado`, calcular costo de envío consultando `POST https://tarifas.transportadora.test/v1/cotizar` con credencial por configuración. | Servicio dedicado `App\Services\TransportadoraService` usando `Illuminate\Support\Facades\Http` con autenticación Bearer (`TRANSPORTADORA_API_KEY`). Incluye manejo resiliente de errores/contingencia local documentada cuando el dominio `.test` no esté disponible en entorno local, y pruebas automatizadas con `Http::fake()`. |
| **6** | Los pedidos `entregados` no se pueden modificar. El administrador debe poder corregir cualquier pedido. | Regla de negocio centralizada en `PedidoPolicy` / capa de controlador: bloquea cualquier cambio sobre un pedido en estado `entregado` para usuarios operadores (HTTP 403), pero permite edición/corrección total (estado, monto, cliente, fecha, costo de envío) cuando el usuario tiene rol `admin`. Incluye selector rápido de rol (Operador / Administrador) en la barra superior para facilitar pruebas. |
| **7** | *(Opcional - Suma puntos)* Listado en React o Angular consumiendo `GET /api/pedidos?estado=...`. | Vista/Aplicación en **React 18** integrada en el proyecto que consume `GET /api/pedidos?estado=...` en tiempo real, permite filtrar por estado, visualizar costos de envío y totales, e interactuar con los estados de los pedidos. |

---

## 2. Diseño del Modelo de Datos (Pregunta 1.4 Extendida)

### Tabla `clientes`
- `id` (BIGINT, PK, Auto-increment)
- `nombre` (VARCHAR 150, NOT NULL)
- `documento` (VARCHAR 50, UNIQUE, NULLABLE — NIT/CC)
- `email` (VARCHAR 150, UNIQUE, NOT NULL)
- `telefono` (VARCHAR 50, NULLABLE)
- `ciudad` (VARCHAR 100, NOT NULL — usada para cotización de despacho)
- `direccion` (VARCHAR 255, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMPS)

### Tabla `pedidos`
- `id` (BIGINT, PK, Auto-increment)
- `cliente_id` (BIGINT, FK -> `clientes.id`, ON DELETE CASCADE)
- `fecha` (DATE, NOT NULL — fecha del pedido para el reporte mensual)
- `estado` (ENUM / VARCHAR: `'pendiente'`, `'despachado'`, `'entregado'`, DEFAULT `'pendiente'`, INDEX)
- `total` (DECIMAL(12,2), NOT NULL — valor de la venta del pedido)
- `peso_kg` (DECIMAL(8,2), DEFAULT 1.00 — dato logístico para la transportadora)
- `ciudad_destino` (VARCHAR 100, NOT NULL — ciudad de entrega)
- `costo_envio` (DECIMAL(12,2), NULLABLE — calculado al pasar a `despachado`)
- `referencia_cotizacion` (VARCHAR 100, NULLABLE — trazabilidad de la respuesta de la API)
- `notas` (TEXT, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMPS)

---

## 3. Fases de Ejecución y Commits Planificados

Para cumplir con el entregable de **"Repositorio público con commits frecuentes y mensajes claros"**, el desarrollo se dividirá en etapas atómicas:

1. **Fase 1 — Inicialización y Planificación (`chore: initialize Laravel project and action plan`)**
   - Creación del proyecto Laravel, configuración de SQLite/MySQL lista para usar y registro del `PLAN_DE_ACCION.md`.
2. **Fase 2 — Base de Datos, Modelos, Migraciones, Seeders y SQL (`feat(db): add clientes and pedidos migrations, models, seeders and SQL script`)**
   - Creación de modelos `Cliente` y `Pedido` con relaciones Eloquent (`hasMany`, `belongsTo`) y constantes de estados.
   - Creación de migraciones y `DatabaseSeeder` con datos realistas del mes actual y meses previos.
   - Generación de `database/schema.sql` para compatibilidad directa con motores SQL.
3. **Fase 3 — Integración con API de Transportadora y Reglas de Negocio (`feat(shipping): implement TransportadoraService and admin/delivered protection rules`)**
   - Implementación de `TransportadoraService` (`POST https://tarifas.transportadora.test/v1/cotizar`) con soporte de credenciales en `.env` (`TRANSPORTADORA_API_URL`, `TRANSPORTADORA_API_KEY`).
   - Implementación de control de acceso y bloqueo de modificación de pedidos `entregados` para no-administradores, permitiendo corrección completa al administrador.
4. **Fase 4 — Endpoint API REST y Reporte Mensual (`feat(api): add GET /api/pedidos endpoint and monthly sales by client report`)**
   - Implementación de `GET /api/pedidos?estado=pendiente` (y endpoints auxiliares para cambio de estado y reporte).
   - Implementación de la consulta de reporte mensual de ventas por cliente ordenada de mayor a menor.
5. **Fase 5 — Interfaz Web (Blade) + Listado en React (Punto 7) (`feat(ui): build web dashboard, forms, monthly report and React orders module`)**
   - Construcción del módulo web con listado, filtro por estado, formulario de creación, cambio de estado, formulario de corrección de administrador y tabla de reporte del mes actual.
   - Construcción del componente/vista en **React** consumiendo `GET /api/pedidos?estado=...`.
6. **Fase 6 — Pruebas Automatizadas (`test: add feature tests for API, shipping calculation, delivered lock and monthly report`)**
   - Pruebas con PHPUnit para verificar todos los requisitos (1 al 7), incluyendo `Http::fake()` para la API de tarifas de la transportadora.
7. **Fase 7 — Documentación, README e Informe Final (`docs: add README, 2-page technical report and AI prompt registry`)**
   - Redacción de `README.md` paso a paso.
   - Redacción de `INFORME_TECNICO.md` (máximo 2 páginas) con registro detallado de prompts de IA y correcciones realizadas.

# UVA3 Logística — Módulo de Pedidos (Parte 5: Reto Práctico)

Módulo de gestión de pedidos, cotización automática de envíos y reporte mensual de ventas por cliente para **UVA3**, desarrollado en **Laravel (PHP 8.4)** con listado interactivo en **React 18**.

---

## Características Implementadas

1. **Base de Datos (`clientes` y `pedidos`):**
   - Migraciones de Laravel (`database/migrations/`), datos de prueba (`database/seeders/DatabaseSeeder.php`) y script SQL independiente (`database/schema.sql`).
   - Campo `estado` con los valores permitidos: `pendiente`, `despachado`, `entregado`.
2. **Listado y Formularios Web:**
   - Filtro rápido por estado (`todos`, `pendiente`, `despachado`, `entregado`).
   - Formulario para registrar nuevos pedidos y selector para cambiar el estado de cualquier pedido.
3. **API JSON (`GET /api/pedidos?estado=pendiente`):**
   - Endpoint REST que responde en JSON con los pedidos filtrados por estado e información del cliente asociado.
4. **Reporte de Ventas del Mes Actual:**
   - Consulta agregada del total vendido por cliente en el mes en curso, ordenada de **mayor a menor** (`ORDER BY total_vendido DESC`), excluyendo meses anteriores.
5. **Integración con API de Tarifas de Transportadora:**
   - Al marcar un pedido como `despachado`, `TransportadoraService` consulta `POST https://tarifas.transportadora.test/v1/cotizar` enviando la credencial configurada en `.env` (`TRANSPORTADORA_API_KEY`).
   - Incluye respaldo local automático si el dominio `.test` no resuelve en entorno sin mock de red, y pruebas automatizadas con `Http::fake()`.
6. **Protección de Pedidos Entregados y Rol Administrador:**
   - Los pedidos en estado `entregado` son inmutables para operadores (bloqueados en UI y con respuesta `HTTP 403` en backend).
   - El **Administrador** puede corregir cualquier pedido (incluso los entregados) usando el selector de rol en la barra superior o la cabecera `X-Admin-Mode: 1`.
7. **Listado en React 18 (Punto 7 — Opcional):**
   - Componente en React integrado en el panel principal (y código fuente en `resources/js/components/PedidosReactApp.jsx`) que consume `GET /api/pedidos?estado=pendiente` en vivo.

---

## Requisitos Previos

- **PHP** >= 8.2 (con extensiones `pdo_sqlite` o `pdo_mysql`, `curl`, `mbstring`)
- **Composer** >= 2.x
- **SQLite** (configurado por defecto para ejecución inmediata) o **MySQL/MariaDB**

---

## Instalación y Ejecución Paso a Paso

### 1. Clonar el repositorio e instalar dependencias
```bash
git clone <URL_DEL_REPOSITORIO>
cd Proyectouve
composer install
```

### 2. Configurar variables de entorno
```bash
cp .env.example .env
php artisan key:generate
```

En el archivo `.env` puedes configurar la credencial de la transportadora entregada por solicitud:
```env
TRANSPORTADORA_API_URL=https://tarifas.transportadora.test/v1/cotizar
TRANSPORTADORA_API_KEY=uva3-token-solicitud-2026
TRANSPORTADORA_CIUDAD_ORIGEN="Bogotá D.C."
TRANSPORTADORA_FALLBACK_ENABLED=true
```

### 3. Crear base de datos y poblar datos de prueba (Migraciones + Seeders)
Para SQLite (por defecto):
```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate:fresh --seed
```
*(Alternativamente, si deseas importar el script SQL directamente en MySQL/MariaDB, puedes usar el archivo `database/schema.sql`).*

### 4. Iniciar el servidor de desarrollo
```bash
php artisan serve
```
Abre en tu navegador: **[http://localhost:8000](http://localhost:8000)**

---

## Endpoints API Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/pedidos?estado=pendiente` | **(Requisito 3)** Retorna en JSON los pedidos filtrados por `pendiente`, `despachado` o `entregado`. |
| `POST` | `/api/pedidos` | Crea un pedido nuevo en JSON. |
| `PATCH` | `/api/pedidos/{id}/estado` | **(Requisitos 5 y 6)** Cambia el estado; si pasa a `despachado`, cotiza costo de envío. Bloquea si está `entregado` y no es admin (`X-Admin-Mode: 1`). |
| `PUT` | `/api/pedidos/{id}` | **(Requisito 6)** Corrección integral del pedido (habilitado en `entregado` para el administrador). |
| `GET` | `/api/reportes/ventas-mes` | **(Requisito 4)** Retorna en JSON el ranking de ventas por cliente en el mes actual de mayor a menor. |

---

## Ejecución de Pruebas Automatizadas

```bash
php artisan test
```
Incluye pruebas de integración en `tests/Feature/ModuloPedidosTest.php` que verifican el endpoint JSON, el reporte mensual, la cotización `POST https://tarifas.transportadora.test/v1/cotizar` y las reglas de bloqueo/corrección de pedidos entregados.

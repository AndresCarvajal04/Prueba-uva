# UVA3 Logística Nacional — Sistema Corporativo de Gestión de Pedidos y Despachos

Plataforma web y API REST para la administración de clientes corporativos, control de órdenes de pedido, liquidación automática de tarifas de transporte nacional y consolidación mensual de facturación por cliente.

Desarrollado con **Laravel (PHP 8.4)**, **Eloquent ORM**, **Tailwind CSS** y **React 18**.

---

## Tabla de Contenidos

1. [Descripción General y Arquitectura](#1-descripción-general-y-arquitectura)
2. [Flujo Operativo Paso a Paso del Sistema](#2-flujo-operativo-paso-a-paso-del-sistema)
3. [Perfiles de Operación: Empleado vs. Administrador (CRUD)](#3-perfiles-de-operación-empleado-vs-administrador-crud)
4. [Estructura de Carpetas del Proyecto](#4-estructura-de-carpetas-del-proyecto)
5. [Requisitos del Entorno](#5-requisitos-del-entorno)
6. [Guía de Instalación y Ejecución Paso a Paso](#6-guía-de-instalación-y-ejecución-paso-a-paso)
7. [Configuración de la API de Transportadora](#7-configuración-de-la-api-de-transportadora)
8. [Documentación de Endpoints API REST](#8-documentación-de-endpoints-api-rest)
9. [Pruebas Automatizadas (PHPUnit)](#9-pruebas-automatizadas-phpunit)

---

## 1. Descripción General y Arquitectura

El sistema resuelve la operación diaria de despachos nacionales bajo una arquitectura desacoplada en capas:

- **Capa de Presentación Corporativa (SSR + SPA):**
  - Interfaz principal en **Laravel Blade + Tailwind CSS** diseñada bajo lineamientos de sistema empresarial (TMS/ERP), con navegación guiada por flujo de proceso, maestro de clientes, control de órdenes y reporte ejecutivo mensual.
  - Consola interactiva en **React 18** integrada en tiempo real que consume directamente el servicio JSON `/api/pedidos`.
- **Capa de Servicios de Dominio (`app/Services/`):**
  - `TransportadoraService`: Gestiona la cotización de envíos mediante petición `POST https://tarifas.transportadora.test/v1/cotizar` autenticada por token, con motor de cálculo de contingencia local cuando el entorno se ejecuta sin resolución DNS para el dominio `.test`.
  - `ReporteVentasService`: Ejecuta la agregación contable del total vendido por cliente en el mes en curso, ordenado de mayor a menor facturación.
- **Capa de Persistencia (`database/`):**
  - Modelos relacionales `Cliente` (`1:N`) y `Pedido` con estados controlados (`pendiente`, `despachado`, `entregado`), migraciones, seeders dinámicos y script SQL estándar (`database/schema.sql`).

---

## 2. Flujo Operativo Paso a Paso del Sistema

El sistema cuenta con un flujo guiado de **4 etapas** visible en la parte superior del panel principal:

1. **Paso 1 — Maestro de Clientes (`#seccion-clientes`):**
   - Verifique que el cliente exista en el **Directorio Maestro de Clientes Corporativos** o regístrelo indicando su Razón Social, NIT/Documento, Correo, Teléfono y Ciudad principal de entrega.
2. **Paso 2 — Registro de Orden de Pedido (`#formulario-nuevo-pedido`):**
   - En el panel lateral **Nueva Orden de Pedido**, seleccione el cliente (el sistema autocompleta automáticamente su ciudad de destino), ingrese la fecha, el valor comercial de la venta y el peso bruto en kilogramos.
   - El pedido queda registrado inicialmente en estado **`Pendiente`**.
3. **Paso 3 — Despacho y Liquidación Automática de Flete (`#seccion-ordenes`):**
   - En la tabla **Registro y Control de Órdenes de Pedido**, cambie el estado del pedido a **`Despachado`** y pulse **Aplicar**.
   - El sistema invoca automáticamente a `TransportadoraService`, consulta la API de tarifas (`POST /v1/cotizar`), asigna el **Costo de Envío (`costo_envio`)** y registra el código de guía/referencia (`referencia_cotizacion`).
4. **Paso 4 — Entrega, Cierre de Seguridad y Consolidado Mensual (`#seccion-reporte`):**
   - Cuando la mercancía llega al destinatario, cambie el estado a **`Entregado`**.
   - Una vez en estado **`Entregado`**, la orden queda cerrada e **inmutable para el perfil de Empleado Operativo**.
   - En cualquier momento puede consultar el panel **Ventas por Cliente — Mes Actual**, que consolida la facturación del mes en curso ordenada de mayor a menor.

---

## 3. Perfiles de Operación: Empleado vs. Administrador (CRUD)

En la barra superior derecha del sistema se encuentra el menú desplegable **`Perfil Activo`**, el cual permite alternar inmediatamente entre los dos niveles de autorización:

### A. Perfil: Empleado Operativo
- Puede crear nuevos pedidos y registrar nuevos clientes.
- Puede avanzar el estado de órdenes en estado `Pendiente` o `Despachado`.
- **Restricción:** No puede modificar, editar ni eliminar órdenes que ya se encuentren en estado **`Entregado`** (la interfaz muestra `Orden Cerrada (Inmutable)` y el backend rechaza cualquier intento con código `HTTP 403`).

### B. Perfil: Administrador General (`¿Dónde itera y edita el Administrador?`)
Al seleccionar **`Administrador General`** en el menú desplegable superior:
1. **CRUD Completo de Órdenes de Pedido (`#seccion-ordenes` y `#panel-edicion-orden`):**
   - Se desbloquean todas las órdenes del sistema, **incluidas las que están en estado `Entregado`**.
   - En cada fila de la tabla de pedidos se habilitan los botones:
     - **`Editar`**: Abre el **Módulo de Edición y Auditoría de Orden** en la parte superior, permitiendo corregir el cliente titular, fecha, estado operativo, valor comercial, peso en kg, ciudad de destino, costo de envío manual o marcar la casilla **"Recalcular tarifa automáticamente con la transportadora"**.
     - **`Eliminar`**: Permite eliminar órdenes de pedido con confirmación de seguridad.
2. **CRUD Completo del Maestro de Clientes (`#seccion-clientes`):**
   - En el **Directorio Maestro de Clientes Corporativos** se habilita la columna **Gestión Administrador** con los botones **`Editar`** (para modificar NIT, razón social, ciudad, correo o dirección de cualquier cliente) y **`Eliminar`**.
3. **Consola API React (`#react-pedidos-root`):**
   - El selector de perfil de la consola React se sincroniza con el modo Administrador (enviando la cabecera `X-Admin-Mode: 1`), permitiendo transicionar estados de cualquier orden directamente vía API REST.

---

## 4. Estructura de Carpetas del Proyecto

```text
Proyectouve/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── PedidoController.php       # Controlador API REST (JSON serializado con API Resources)
│   │   │   ├── ClienteWebController.php       # Controlador Web del Directorio Maestro de Clientes (SRP)
│   │   │   ├── PedidoWebController.php        # Controlador Web de Órdenes de Pedido, Flujo y Tablero (SRP)
│   │   │   └── ReporteWebController.php       # Controlador Web del Consolidado Mensual de Ventas (SRP)
│   │   ├── Middleware/
│   │   │   └── SecurityHeadersMiddleware.php  # Cabeceras HTTP de seguridad (nosniff, SAMEORIGIN, XSS-Protection)
│   │   ├── Requests/
│   │   │   ├── ClienteRequest.php             # Validación y sanitización desacoplada para Clientes
│   │   │   ├── StorePedidoRequest.php         # Validación y sanitización desacoplada para creación de Pedidos
│   │   │   └── UpdatePedidoRequest.php        # Validación y sanitización desacoplada para edición de Pedidos
│   │   └── Resources/
│   │       ├── ClienteResource.php            # Serialización controlada y segura de la entidad Cliente
│   │       └── PedidoResource.php             # Serialización controlada y segura de la entidad Pedido
│   ├── Models/
│   │   ├── Cliente.php                        # Modelo Eloquent de Clientes (relación hasMany con Pedido)
│   │   ├── Pedido.php                         # Modelo Eloquent de Pedidos (estados y regla puedeSerModificadoPor)
│   │   └── User.php                           # Modelo de Usuarios con atributo booleano is_admin
│   └── Services/
│       ├── ReporteVentasService.php           # Lógica de negocio del reporte mensual de ventas por cliente (DESC)
│       └── TransportadoraService.php          # Cliente HTTP hacia POST https://tarifas.transportadora.test/v1/cotizar
├── bootstrap/
│   └── app.php                                # Configuración de rutas Web, API, Middleware de seguridad y JSON
├── config/
│   ├── database.php                           # Configuración de conexiones SQLite / MySQL
│   └── services.php                           # Configuración de credenciales y URL de la API de Transportadora
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2026_10_20_000001_create_clientes_table.php
│   │   └── 2026_10_20_000002_create_pedidos_table.php
│   ├── seeders/
│   │   └── DatabaseSeeder.php                 # Datos de prueba (usuarios, 5 clientes y 10 pedidos del mes actual y previo)
│   ├── database.sqlite                        # Base de datos SQLite lista para ejecución local
│   └── schema.sql                             # Script SQL independiente (DDL + DML + consulta de reporte mensual)
├── public/
│   ├── css/
│   │   └── corporate.css                      # Hoja de estilos corporativa desacoplada (variables, tablas, badges, inputs)
│   └── js/
│       ├── pedidos-form.js                    # Lógica JS desacoplada para autocompletado de formulario de pedidos
│       └── tablero-react.js                   # Módulo JS/React 18 desacoplado para el Tablero Dinámico
├── resources/
│   ├── js/
│   │   └── components/
│   │       └── PedidosReactApp.jsx            # Componente fuente en React 18 para el Tablero Dinámico
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php                  # Layout corporativo compartido (barra de navegación y selector de perfil)
│       ├── clientes/
│       │   └── index.blade.php                # Sección 3: Directorio Maestro de Clientes (CRUD)
│       ├── pedidos/
│       │   ├── index.blade.php                # Sección 1: Gestión y Control de Órdenes de Pedido
│       │   ├── crear.blade.php                # Sección 2: Formulario de Apertura de Nueva Orden
│       │   ├── editar.blade.php               # Vista dedicada de Edición y Auditoría de Orden
│       │   ├── monitor-react.blade.php        # Sección 5: Tablero Dinámico en Tiempo Real (React 18)
│       │   └── guia.blade.php                 # Sección 6: Manual Operativo Paso a Paso y Matriz de Permisos
│       └── reportes/
│           └── ventas.blade.php               # Sección 4: Consolidado Mensual de Ventas por Cliente (Mayor a Menor)
├── routes/
│   ├── api.php                                # Definición de endpoints REST bajo el prefijo /api
│   └── web.php                                # Rutas de la aplicación web y acciones CRUD
├── tests/
│   └── Feature/
│       ├── ExampleTest.php                    # Prueba de carga e integridad del panel principal
│       └── ModuloPedidosTest.php              # Suite de pruebas de integración (API JSON, cotización, bloqueo y reporte)
├── .env.example                               # Plantilla de variables de entorno (incluye TRANSPORTADORA_*)
├── INFORME_TECNICO.md                         # Informe ejecutivo de 2 páginas y registro de prompts de IA
├── PLAN_DE_ACCION.md                          # Plan de acción arquitectónico y fases del proyecto
└── README.md                                  # Documentación general e instrucciones de instalación
```

---

## 5. Requisitos del Entorno

- **PHP** >= 8.2 (probado en PHP 8.4) con las extensiones: `pdo`, `pdo_sqlite` (o `pdo_mysql`), `mbstring`, `curl`, `openssl`.
- **Composer** >= 2.x.
- **Base de Datos:** SQLite (preconfigurada por defecto, no requiere servidor externo) o MySQL / MariaDB 8.0+.

---

## 6. Guía de Instalación y Ejecución Paso a Paso

### Paso 1: Clonar el repositorio e instalar dependencias PHP
```bash
git clone https://github.com/AndresCarvajal04/Prueba-uva.git
cd Prueba-uva
composer install
```

### Paso 2: Crear el archivo de entorno y generar la llave de aplicación
En Linux / macOS / Git Bash:
```bash
cp .env.example .env
php artisan key:generate
```
En Windows PowerShell:
```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### Paso 3: Configurar e inicializar la Base de Datos

#### Opción A — Usando SQLite (Recomendado para ejecución inmediata)
```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate:fresh --seed
```

#### Opción B — Usando MySQL / MariaDB
1. Edite el archivo `.env` con las credenciales de su servidor MySQL:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=uva3_logistica
   DB_USERNAME=root
   DB_PASSWORD=
   ```
2. Ejecute las migraciones y los datos de prueba:
   ```bash
   php artisan migrate:fresh --seed
   ```
   *(También dispone del archivo `database/schema.sql` si desea importar las tablas y datos directamente desde un cliente SQL).*

### Paso 4: Levantar el servidor de desarrollo
```bash
php artisan serve
```
Acceda desde su navegador a:
- **Panel Corporativo Principal:** [http://localhost:8000](http://localhost:8000)
- **Endpoint JSON (Pedidos Pendientes):** [http://localhost:8000/api/pedidos?estado=pendiente](http://localhost:8000/api/pedidos?estado=pendiente)
- **Endpoint JSON (Reporte Ventas del Mes):** [http://localhost:8000/api/reportes/ventas-mes](http://localhost:8000/api/reportes/ventas-mes)

---

## 7. Configuración de la API de Transportadora

En el archivo `.env` se configuran los parámetros de conexión hacia el servicio de cotización de la transportadora:

```env
TRANSPORTADORA_API_URL=https://tarifas.transportadora.test/v1/cotizar
TRANSPORTADORA_API_KEY=uva3-token-solicitud-2026
TRANSPORTADORA_CIUDAD_ORIGEN="Bogotá D.C."
TRANSPORTADORA_FALLBACK_ENABLED=true
```

- Cuando un pedido pasa al estado **`despachado`**, `TransportadoraService` envía una petición `POST` a `TRANSPORTADORA_API_URL` con cabecera `Authorization: Bearer <TRANSPORTADORA_API_KEY>` y cuerpo JSON (`pedido_id`, `ciudad_origen`, `ciudad_destino`, `peso_kg`, `valor_declarado`).
- Si `TRANSPORTADORA_FALLBACK_ENABLED=true` y el dominio `.test` no resuelve en la red local del evaluador, el sistema registra el intento en el log y calcula automáticamente la tarifa con el tabulador nacional de respaldo para no interrumpir la operación.

---

## 8. Documentación de Endpoints API REST

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/pedidos` | Lista todos los pedidos en formato JSON. Soporta filtro `?estado=pendiente\|despachado\|entregado`. |
| `POST` | `/api/pedidos` | Registra un nuevo pedido (`cliente_id`, `total`, `peso_kg`, `ciudad_destino`, `estado`). |
| `PATCH` | `/api/pedidos/{id}/estado` | Cambia el estado del pedido. Si es `despachado`, liquida el costo de envío. Bloquea si está `entregado` salvo cabecera `X-Admin-Mode: 1`. |
| `PUT` | `/api/pedidos/{id}` | Actualización completa de un pedido (habilitada sobre pedidos entregados con `X-Admin-Mode: 1`). |
| `DELETE` | `/api/pedidos/{id}` | Elimina un pedido del sistema (requiere `X-Admin-Mode: 1`). |
| `GET` | `/api/reportes/ventas-mes` | Retorna el consolidado de ventas por cliente del mes actual, ordenado de mayor a menor. |
| `GET` | `/api/clientes` | Retorna el directorio de clientes corporativos. |

### Ejemplo de Respuesta: `GET /api/pedidos?estado=pendiente`
```json
{
  "ok": true,
  "filtro_estado": "pendiente",
  "total_registros": 3,
  "data": [
    {
      "id": 4,
      "cliente_id": 3,
      "fecha": "2026-10-07",
      "estado": "pendiente",
      "total": "2100000.00",
      "peso_kg": "15.00",
      "ciudad_destino": "Barranquilla",
      "costo_envio": null,
      "referencia_cotizacion": null,
      "cliente": {
        "id": 3,
        "nombre": "Grupo Empresarial Caribe S.A.",
        "ciudad": "Barranquilla"
      }
    }
  ]
}
```

---

## 9. Pruebas Automatizadas (PHPUnit)

Para ejecutar la suite completa de pruebas de integración:

```bash
php artisan test
```

Las pruebas ubicadas en `tests/Feature/ModuloPedidosTest.php` verifican:
1. Respuesta JSON y filtrado en `GET /api/pedidos?estado=pendiente`.
2. Cálculo exacto y ordenamiento descendente del reporte mensual de ventas por cliente (excluyendo meses anteriores).
3. Cotización de tarifa de envío mediante `Http::fake()` hacia `POST https://tarifas.transportadora.test/v1/cotizar` al marcar un pedido como `despachado`.
4. Bloqueo de modificación de pedidos en estado `entregado` para empleados/operadores (`HTTP 403`) y autorización de edición/corrección para el perfil `Administrador`.

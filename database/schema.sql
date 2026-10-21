-- =========================================================================
-- UVA3 Logística — Script SQL de Base de Datos y Datos de Prueba
-- Parte 5: Reto práctico — Módulo de pedidos (Modelo Pregunta 1.4)
-- Compatible con MySQL / MariaDB / PostgreSQL / SQLite
-- =========================================================================

DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS clientes;

-- 1. Tabla de Clientes
CREATE TABLE clientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    documento VARCHAR(50) NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(50) NULL,
    ciudad VARCHAR(100) NOT NULL,
    direccion VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabla de Pedidos (incluye campo estado: pendiente, despachado, entregado)
CREATE TABLE pedidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'despachado', 'entregado')),
    total DECIMAL(12, 2) NOT NULL,
    peso_kg DECIMAL(8, 2) NOT NULL DEFAULT 1.00,
    ciudad_destino VARCHAR(100) NOT NULL,
    costo_envio DECIMAL(12, 2) NULL,
    referencia_cotizacion VARCHAR(100) NULL,
    notas TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedidos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_pedidos_fecha ON pedidos(fecha);

-- 3. Datos de Prueba — Clientes
INSERT INTO clientes (id, nombre, documento, email, telefono, ciudad, direccion) VALUES
(1, 'Distribuidora Andina S.A.S.', '900.412.881-1', 'compras@andinadist.co', '604 312 4500', 'Medellín', 'Cra. 43A # 14-50, El Poblado'),
(2, 'Comercializadora del Pacífico Ltda.', '800.198.332-4', 'logistica@pacifico.com.co', '602 489 1120', 'Cali', 'Av. 6N # 28N-102, Santa Mónica'),
(3, 'Grupo Empresarial Caribe S.A.', '901.005.719-8', 'pedidos@grupocaribe.co', '605 385 9000', 'Barranquilla', 'Vía 40 # 73-290, Zona Industrial'),
(4, 'Almacenes Santander Express', '890.204.511-3', 'abastecimiento@santanderexpress.co', '607 643 2210', 'Bucaramanga', 'Calle 36 # 19-45, Centro'),
(5, 'Inversiones Cafeteras del Quindío', '900.774.109-6', 'contacto@invcafeteras.co', '606 745 8830', 'Pereira', 'Av. Circunvalar # 8-60');

-- 4. Datos de Prueba — Pedidos
INSERT INTO pedidos (cliente_id, fecha, estado, total, peso_kg, ciudad_destino, costo_envio, referencia_cotizacion, notas) VALUES
(1, '2026-10-02', 'entregado', 2450000.00, 18.50, 'Medellín', 64250.00, 'COT-UVA3-1001', 'Lote de insumos industriales - Entregado en bodega principal'),
(1, '2026-10-05', 'despachado', 1800000.00, 12.00, 'Medellín', 45000.00, 'COT-UVA3-1002', 'Reposición urgente de catálogo Q4'),
(3, '2026-10-03', 'entregado', 3500000.00, 32.00, 'Barranquilla', 118000.00, 'COT-UVA3-1003', 'Carga pesada costa atlántica'),
(3, '2026-10-07', 'pendiente', 2100000.00, 15.00, 'Barranquilla', NULL, NULL, 'Pendiente de despacho y cotización en transportadora'),
(2, '2026-10-04', 'despachado', 1950000.00, 14.20, 'Cali', 52800.00, 'COT-UVA3-1004', 'Envío refrigerado ruta occidente'),
(2, '2026-10-08', 'pendiente', 1200000.00, 8.50, 'Cali', NULL, NULL, 'Alistamiento en centro de distribución'),
(4, '2026-10-06', 'pendiente', 1480000.00, 9.00, 'Bucaramanga', NULL, NULL, 'Mercancía frágil - requiere embalaje reforzado'),
(5, '2026-10-09', 'entregado', 890000.00, 5.40, 'Pereira', 28500.00, 'COT-UVA3-1005', 'Entregado a satisfacción'),
(4, '2026-09-11', 'entregado', 4900000.00, 40.00, 'Bucaramanga', 142000.00, 'COT-UVA3-0901', 'Pedido histórico del mes pasado');

-- 5. Consulta de Referencia — Reporte del total vendido por cliente en el mes actual (de mayor a menor)
-- SELECT
--     c.id,
--     c.nombre,
--     c.ciudad,
--     COUNT(p.id) AS cantidad_pedidos,
--     SUM(p.total) AS total_vendido,
--     COALESCE(SUM(p.costo_envio), 0) AS total_envios
-- FROM clientes c
-- INNER JOIN pedidos p ON p.cliente_id = c.id
-- WHERE p.fecha >= '2026-10-01' AND p.fecha <= '2026-10-31'
-- GROUP BY c.id, c.nombre, c.ciudad
-- ORDER BY total_vendido DESC;

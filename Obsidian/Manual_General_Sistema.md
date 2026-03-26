# Manual General del Sistema — Terracota POS

> **Sistema:** Terracota Construcciones — Punto de Venta e Inventario
> **Stack:** Laravel 12 + Filament v3 + SQLite (dev) / PostgreSQL (prod)
> **Versión del manual:** 1.3 — 26/03/2026

---

## Índice General

1. [¿Qué es este sistema?](#1-qué-es-este-sistema)
2. [Arquitectura general](#2-arquitectura-general)
3. [Roles y accesos](#3-roles-y-accesos)
4. [Flujo operativo diario](#4-flujo-operativo-diario)
5. [Módulo de Caja](#5-módulo-de-caja)
6. [Módulo de Ventas](#6-módulo-de-ventas)
7. [Presupuestos y Notas de Pedido](#7-presupuestos-y-notas-de-pedido)
8. [Sistema de Créditos](#8-sistema-de-créditos)
9. [Módulo de Compras](#9-módulo-de-compras)
10. [Módulo de Inventario](#10-módulo-de-inventario)
11. [Facturación Electrónica — SIFEN](#11-facturación-electrónica--sifen)
12. [Configuración del sistema](#12-configuración-del-sistema)
13. [Seguridad y auditoría](#13-seguridad-y-auditoría)
14. [Resolución de problemas](#14-resolución-de-problemas)
15. [Manuales por rol](#15-manuales-por-rol)

---

## 1. ¿Qué es este sistema?

El sistema **Terracota POS** es una plataforma web de **Punto de Venta e Inventario** diseñada para Terracota Construcciones. Permite:

- Registrar ventas al contado y a crédito con comprobante imprimible
- Controlar el inventario en tiempo real por almacén y sucursal
- Gestionar compras a proveedores con recepción de mercadería
- Administrar la cartera de créditos de clientes con calendario de vencimientos
- Emitir facturas electrónicas compatibles con SIFEN v150 (SET Paraguay)
- Auditar cada operación con registro completo de quién hizo qué y cuándo

**Panel de administración:** `http://[servidor]/admin`

---

## 2. Arquitectura general

```
Empresa
  └── Sucursal (uno o más)
        ├── Usuarios (cajeros, supervisores)
        ├── Almacenes (uno o más por sucursal)
        │     └── Stock (por variante de producto)
        ├── Cajas Registradoras
        └── Ventas / Compras / Ajustes
```

### Datos compartidos entre sucursales

- Productos, Categorías, Proveedores, Clientes → visibles en todas las sucursales
- Stock, Ventas, Compras, Cajas → **filtrados por sucursal automáticamente** según el usuario logueado

> **BranchScope:** El sistema aplica un filtro global transparente. Cada usuario solo ve los datos de su sucursal. El rol `admin` puede ver todo.

---

## 3. Roles y accesos

| Rol | Quién lo usa | Acceso principal |
|-----|-------------|-----------------|
| `admin` | Dueño / Gerente general | Todo, todas las sucursales |
| `supervisor` | Gerente de sucursal | Todo, solo su sucursal |
| `vendedor` | Cajero / Vendedor | Ventas, caja, productos (lectura) |
| `almacenero` | Encargado de depósito | Compras, ajustes de inventario |
| `cobrador` | Cobrador de créditos | Calendario de créditos, registrar cobros |

Para ver el detalle completo de permisos por rol, consultá [[Manual_Roles_Permisos]].

---

## 4. Flujo operativo diario

```
Inicio del turno
    │
    ▼
[1] Cajero abre su caja (monto de apertura en efectivo)
    │
    ▼
[2] Clientes llegan → se registran ventas
    │
    ├── Contado → descuenta stock + suma a caja
    ├── Crédito → descuenta stock + suma saldo al cliente
    └── Nota de Pedido → no descuenta nada (presupuesto)
    │
    ▼
[3] Compras del día (almacenero recibe mercadería → sube stock)
    │
    ▼
[4] Cobrador registra pagos de clientes con crédito
    │
    ▼
[5] Cierre de turno: cajero cuenta efectivo → cierra caja
    │
    ▼
[6] Supervisor revisa reportes + calendario de vencimientos
```

---

## 5. Módulo de Caja

La caja es el punto de control del efectivo. **Debe estar abierta** para poder crear ventas.

### Apertura

1. **Ventas → Cajas → Nueva Caja**
2. Ingresar monto de apertura (efectivo disponible al inicio)
3. Estado: `Abierta`

### Durante el turno

- Cada venta al contado suma automáticamente al saldo esperado de la caja
- Las ventas a crédito **no** afectan la caja
- Una caja abierta puede tener múltiples ventas asociadas

### Cierre

1. Editar la caja → ingresar **Monto de Cierre** (conteo físico)
2. El sistema calcula la diferencia automáticamente
3. Diferencias > 10% generan advertencia (registrar motivo en Notas)

> **Referencia detallada:** [[Manual_Cajero#gestión-de-caja]]

---

## 6. Módulo de Ventas

### Navegación — Tabs del listado

El listado de ventas tiene cuatro pestañas:

| Pestaña | Contenido | Badge |
|---------|-----------|-------|
| **Todas** | Historial completo | — |
| **Notas de Pedido / Presupuestos** | Ventas pendientes (presupuestos sin cobrar) | Naranja: cantidad pendiente |
| **Completadas** | Ventas ya cobradas | — |
| **Créditos Activos** | Ventas a crédito con saldo pendiente | Azul: cantidad activa |

### Crear una venta

**Encabezado:**
- **Cliente**: opcional (Consumidor Final si no se especifica)
- **Método de Pago**: `Contado` o `Crédito`
- **Tipo de Documento**: `Ticket` (interno) o `Factura` (SIFEN/legal)
- **Estado**: `Completado` (descuenta stock) o `Nota de Pedido` (no descuenta)
- **Caja**: asignada automáticamente a tu caja abierta

**Productos:**
- Buscá por nombre o SKU
- Usá **Escanear producto** para lector de código de barras
- Cantidad, precio y subtotal se calculan automáticamente
- Toggle **Precios B2B** para clientes exentos de IVA

**Totales:**
- Desglose por tasa de IVA (Exenta / 5% / 10%)
- Descuento general aplicable
- **TOTAL A COBRAR** destacado visualmente en azul

### Condición de pago y fecha de vencimiento

| Condición | Badge tabla | Efecto |
|-----------|-------------|--------|
| Contado | 🟢 Verde | Suma a caja, no genera deuda |
| Crédito | 🟠 Naranja | Genera saldo en cuenta del cliente, aparece en Calendario |

Al elegir **Crédito**, el sistema sugiere automáticamente una fecha de vencimiento en 30 días. Es editable.

### Acciones disponibles por estado

| Estado venta | Acciones disponibles |
|--------------|---------------------|
| Pendiente | Aprobar a Venta, Cobro Rápido, Presupuesto (PDF) |
| Completada | Imprimir Ticket/Factura, Anular |
| Cancelada | Solo visualización |

### Filtros de la tabla

- Rango de fechas (Desde / Hasta) con indicadores activos
- Estado, Condición de pago, Tipo de documento
- Cliente, Vendedor

> **Referencia detallada:** [[Manual_Cajero]]

---

## 7. Presupuestos y Notas de Pedido

Los **presupuestos** en el sistema son ventas con estado `Pendiente`. Características:

- ✅ Imprimibles como "Presupuesto" (PDF)
- ✅ Convertibles en venta real en cualquier momento
- ❌ No descuentan stock
- ❌ No afectan caja

### Flujo completo

```
Nueva venta
  Estado: "Nota de Pedido"
       │
       ▼
  Imprimir presupuesto → PDF para el cliente
       │
       ▼ (cliente acepta)
  Aprobar a Venta
  ├── Registrar pagos (múltiples métodos)
  └── Stock se descuenta / Caja actualizada
```

Para ver todos los presupuestos activos: tab **Notas de Pedido / Presupuestos** en el listado de ventas.

---

## 8. Sistema de Créditos

### Cómo funciona

1. El cliente tiene crédito habilitado (`is_credit_enabled = true`) con un límite en Gs
2. Al crear una venta con **Método: Crédito**, el saldo queda pendiente
3. El cobrador registra pagos parciales o totales en el **Calendario de Créditos**
4. La fecha de vencimiento se actualiza en cada cobro (desde la fecha del cobro, no de la factura)

### Calendario de Créditos

Menú: **Ventas → Calendario de Créditos**

**Stats en la parte superior:**

| Indicador | Significado |
|-----------|-------------|
| 🔴 Ventas Vencidas | La fecha de vencimiento ya pasó y hay saldo |
| 🟡 Por vencer (7 días) | Vencen dentro de una semana |
| 🟢 Vigentes | Sin urgencia |
| Total Saldo Gs | Suma de todo lo adeudado en el sistema |

**Tabla de ventas a crédito:**

Cada fila = una venta a crédito individual con:
- Comprobante / Cliente
- Total original → Cobrado → **Saldo pendiente** (rojo si debe)
- Fecha de vencimiento con semáforo de colores y descripción ("Vence en 5 días")

**Registrar un cobro:**

Desde cada fila, botón **Registrar Cobro**:

```
Modal de cobro
  ├── Resumen visual: Total · Cobrado · Saldo
  ├── Monto a cobrar (puede ser parcial)
  ├── Método: Efectivo / Transferencia / Tarjeta / Cheque / QR
  ├── Fecha del cobro (hoy por defecto)
  ├── ¿Queda saldo? → Nueva fecha de vencimiento (calculada desde el cobro)
  └── Notas / Referencia
```

> **Regla clave:** La fecha de vencimiento se basa en **cuándo el cliente paga**, no en cuándo se emitió la factura. Si hoy cobra 50% y fija 30 días más, el próximo vencimiento es en 30 días desde hoy.

Cuando el saldo llega a cero, la venta desaparece del calendario automáticamente.

> **Referencia detallada:** [[Manual_Admin#6-sistema-de-créditos-a-clientes]]

---

## 9. Módulo de Compras

Gestiona el ingreso de mercadería de proveedores.

### Estados de una compra

```
Pendiente → Recibido
     └→ Cancelado
```

| Estado | Stock |
|--------|-------|
| Pendiente | No modifica stock |
| Recibido | Suma al almacén designado |
| Cancelado | No modifica nada |

### Flujo estándar

1. **Compras → Nueva Compra** → proveedor + items + almacén destino
2. Estado inicial: `Pendiente`
3. Cuando llega la mercadería: **Recibir Productos** → stock actualizado

> **Referencia detallada:** [[Manual_Admin#4-módulo-de-compras]]

---

## 10. Módulo de Inventario

### Stock por almacén

El stock se gestiona **exclusivamente** a través del sistema. No modificar manualmente.

Indicadores de stock:
- 🟢 Verde: por encima del mínimo
- 🟡 Amarillo: igual o por debajo del mínimo (`min_stock`)
- 🔴 Rojo: sin stock

### Ajustes de inventario

Para pérdidas, mermas, roturas o correcciones:
- **Inventario → Ajustes → Nuevo Ajuste**
- Tipo: `entrada` o `salida`
- Motivo obligatorio (queda auditado)

### Conteos físicos

Para auditar el inventario real:
- **Inventario → Conteos Físicos → Nuevo Conteo**
- Ingresar cantidades físicas contadas
- El sistema calcula diferencias y permite aplicarlas como ajuste

### Carga masiva inicial

Ver [[Manual_Carga_Stock]] para el proceso de carga del inventario inicial.

---

## 11. Facturación Electrónica — SIFEN

El sistema implementa SIFEN v150 (SET Paraguay).

### Componentes generados

| Componente | Descripción |
|------------|-------------|
| **CDC 44 dígitos** | Código de Control único por factura. Estructura: tipo(2)+RUC(8)+DV(1)+est(3)+punto(3)+doc(7)+sistema(1)+fecha(8)+emisión(1)+seguridad(9)+DV(1) |
| **URL QR** | Con hash SHA256 usando el CSC (Código de Seguridad del Contribuyente) |
| **XML `<rDE>`** | Documento electrónico completo según estructura SIFEN v150 |
| **Firma digital** | Pendiente: requiere certificado `.p12` emitido por la SET |

### Tipos de documento soportados

- Factura Electrónica (`iTiDE = 1`)
- Nota de Crédito (`iTiDE = 5`)
- Nota de Débito (`iTiDE = 6`)

### Configuración necesaria

Variables de entorno (`.env`):
```env
SIFEN_ENV=test                          # test | production
SIFEN_CSC_ID=0001
SIFEN_CSC_VAL=ABCD0000000000000000000000000000
```

Datos de empresa necesarios (en el modelo `Company`):
- `ruc`, `ruc_dv`, `tipo_contribuyente`, `tipo_regimen`
- `address`, `departamento_code`, `ciudad_code`
- `actividad_eco_code`

> **Documentación técnica:** [[manual_sifen_v150]] | [[Plan_Sifen_XML]]

---

## 12. Configuración del sistema

### Variables de entorno clave

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `APP_URL` | URL del servidor | `http://localhost:8000` |
| `DB_CONNECTION` | Base de datos | `sqlite` |
| `DB_DATABASE` | Ruta del archivo SQLite | `database/database.sqlite` |
| `SIFEN_ENV` | Ambiente SIFEN | `test` |
| `SIFEN_CSC_ID` | ID del CSC | `0001` |
| `SIFEN_CSC_VAL` | Valor del CSC | `ABCD000...` |

### Comandos de administración

```bash
# Instalar desde cero
composer setup

# Iniciar servidor de desarrollo (servidor + queue + vite + logs)
composer dev

# Solo el servidor
php artisan serve

# Aplicar migraciones pendientes
php artisan migrate

# Cargar datos de prueba
php artisan db:seed

# Cargar solo plantillas de comprobantes
php artisan db:seed --class=ReceiptTemplateSeeder

# Backup de la base de datos
php artisan app:backup-sqlite

# Ejecutar tests
composer test
```

---

## 13. Seguridad y auditoría

### Autenticación

- Bloqueo automático tras **5 intentos fallidos** de login
- Sesiones con expiración configurada
- Registro de todos los accesos (éxito y fallo) con IP

### Autorización

- 18 Policies definidas (una por módulo)
- Permisos granulares gestionados por Spatie Permission
- `BranchScope` automático: cada usuario solo accede a su sucursal

### Auditoría

- Cada modelo tiene `LogsActivity` (Spatie Activity Log)
- Cada operación registra: quién, cuándo, qué campo cambió, valor antes/después
- Accesible desde **Auditoría → Actividades**

---

## 14. Resolución de problemas

### Problemas comunes

| Problema | Causa probable | Solución |
|----------|---------------|----------|
| "No se puede crear venta" / botón deshabilitado | Sin caja abierta | Abrir caja desde **Ventas → Cajas** |
| "Stock insuficiente" al crear venta | Menos stock del solicitado | Reducir cantidad o verificar stock en almacén |
| No aparecen plantillas de impresión | Seeder no ejecutado | `php artisan db:seed --class=ReceiptTemplateSeeder` |
| PDF en blanco | Error en template Blade | Revisar logs: `storage/logs/laravel.log` |
| Usuario no ve datos | Sin `branch_id` asignado | Editar usuario y asignar sucursal |
| Error 403 al intentar una acción | Sin permiso | Verificar rol del usuario |
| Base de datos bloqueada (SQLite) | Acceso concurrente | Reiniciar servidor o esperar unos segundos |

### Logs del sistema

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Con composer dev (incluye log viewer)
composer dev
```

### Backup manual

```bash
php artisan app:backup-sqlite
# Genera: storage/backups/backup_YYYY-MM-DD.sqlite
```

Para restaurar: copiar el archivo `.sqlite` sobre `database/database.sqlite`.

> **Runbook completo:** [[Runbook]]

---

## 15. Manuales por rol

| Documento | Para quién |
|-----------|-----------|
| [[Manual_Cajero]] | Vendedores y cajeros |
| [[Manual_Admin]] | Administradores y supervisores |
| [[Manual_Roles_Permisos]] | Detalle de permisos por rol |
| [[Manual_Carga_Stock]] | Carga inicial de inventario |
| [[Manual_Operaciones_Inventario]] | Operaciones de inventario avanzadas |
| [[manual_sifen_v150]] | Documentación técnica SIFEN SET |
| [[Guia_Deploy_Servidor]] | Instalación y deploy en servidor local |
| [[Runbook]] | Operaciones técnicas / IT |
| [[Plan_Mantenimiento_Capacitacion]] | Plan de mantenimiento y capacitación |

---

## Historial de versiones del manual

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0 | 14/03/2026 | Versión inicial — flujo básico de ventas e inventario |
| 1.1 | 20/03/2026 | SIFEN CDC + QR + XML, sistema de créditos v1 |
| 1.2 | 25/03/2026 | Tests (73), IVA Paraguay corregido, SifenXmlService |
| 1.3 | 26/03/2026 | Tabs en ventas (presupuestos), calendario créditos por venta, fecha vencimiento desde cobro, mejoras visuales |
| 1.4 | 26/03/2026 | Importación CSV de productos y stock, guía de deploy en servidor |

---

*Sistema desarrollado para Terracota Construcciones · Laravel 12 + Filament v3*

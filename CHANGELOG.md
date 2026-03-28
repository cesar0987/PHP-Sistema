# Changelog - Terracota POS

Todos los cambios notables del proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto se adhiere temporalmente a Versionado Semántico.

## [1.1.0] - 2026-03-28

### Añadido (Added)
- **Control de Vencimientos y Lotes (FIFO):** Implementación integral de gestión de stock por lotes con fechas de vencimiento.
- **Lógica de Descuento de Stock:** Sistema automatizado que descuenta stock del lote con vencimiento más próximo (FIFO) en ventas y transferencias.
- **Gestión de Compras y Ajustes:** Integración de campos de fecha de vencimiento en los procesos de compra, ajustes de inventario y transferencias entre sucursales.
- **Nuevo Recurso de Lotes:** Interfaz en Filament para la visualización y gestión detallada de lotes de stock (`StockBatch`).
- **Plan de Control de Vencimiento:** Documentación técnica y funcional del sistema de lotes en `Obsidian/Plan_Control_Vencimiento.md`.

## [1.0.5] - 2026-03-27

### Reparado (Fixed)
- **Estabilidad de Migraciones:** Reordenamiento de timestamps en archivos de migración para asegurar la creación correcta de tablas con restricciones de llave foránea (`cash_registers`, `sales`).

## [1.0.4] - 2026-03-26

### Cambiado (Changed)
- **Refactorización Clean Architecture:** Desacoplamiento de servicios (`ReceiptService`, `InventoryService`) del helper `auth()`, implementando inyección de dependencias para mayor testabilidad.
- **Persistencia en Entidades:** Eliminación de lógica de base de datos dentro del modelo `ProductVariant`, moviéndola a la capa de servicios.

## [1.0.3] - 2026-03-25

### Añadido (Added)
- **Facturación Electrónica SIFEN (Paraguay):** Generación de archivos XML bajo el estándar SIFEN v150.
- **Seguridad Fiscal:** Implementación del algoritmo de generación de CDC (Código de Control) de 44 dígitos y URLs para códigos QR oficiales.

## [1.0.2] - 2026-03-15

### Añadido (Added)
- **Abonos a Crédito en Caja:** Integración de cobranzas de cuentas por cobrar en el flujo de efectivo diario de la sucursal.

### Reparado (Fixed)
- **Cierre de Caja:** Corrección en el cálculo de totales esperados, filtrando ventas a crédito y devoluciones para reflejar el efectivo real.

## [1.0.1] - 2026-03-14

### Añadido (Added)
- **Gestión de Créditos Personalizada:** Fecha de vencimiento obligatoria para ventas a crédito y visualización en calendario administrativo.
- **Roles y Permisos:** Implementación del rol "Cobrador" y corrección en la sincronización de permisos mediante `syncRoles()`.

## [1.0.0] - 2026-03-14

### Añadido (Added)
- **Cumplimiento Plan Kendall & Kendall (Fase 1 a 4 completadas)**
- **Segregación de Sucursales:** Modelos y consultas filtradas automáticamente mediante `BranchScope` integrado a la política de roles (Admin exceptuado).
- **Autenticación e Historial Auditado:** Registro de transacciones CRUD con `Spatie Activitylog`.
- **Panel Administrativo:** Interfaz gráfica reactiva para todo el sistema montada sobre `FilamentPHP` y `Livewire`.
- **Módulo Cajas:** Gestión diaria de apertura, cierres e histórico de balance en caja registradora individual por sucursal.
- **Módulo Ventas y Crédito:** Gestión de ventas al contado y de facturación a crédito, limitando el monto a la línea de crédito (`credit_limit`) del cliente.
- **Módulo Inventario y Compras:** Integración y ajuste de stock automatizado post-venta, registro de compras al proveedor que suman a almacenes (`Warehouse`).
- **Módulo Gastos:** Registro y tipificación de gastos operativos (`Expense`).
- **Data Técnica:** 28 Tests Unitarios/Feature documentados. Calidad de código garantizada por Laravel Pint (PSR-12) y análisis estático mediante Larastan.
- **Documentación Operativa:** Manuales para el cajero, el administrador y un Runbook de infraestructura en la carpeta `Obsidian`.

### Cambiado (Changed)
- **Estructura de Base de Datos:** Base de SQLite `database.sqlite` parametrizada y documentada (DER y DFD).
- **Modelo de Ventas:** Adaptado a soft-deletes (`deleted_at`) para retener histórico y justificación de anulaciones de venta (`cancellation_reason`).

### Reparado (Fixed)
- Error de Spatie Permissions (`PermissionDoesNotExist`) donde faltaba la asignación de permisos del módulo Gastos (`ver_gastos`, etc.) en el seeder maestro.
- Error inicial de lock de Base de datos en SQLite que bloqueaba las migraciones en tiempo real durante desarrollo.

---
*(MVP Release Oficial - Fin de la Intervención Inicial Kendall)*

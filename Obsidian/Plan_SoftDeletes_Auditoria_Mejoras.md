# SoftDeletes, Auditoría Completa y Mejoras del Sistema

Plan consolidado de mejoras: SoftDeletes en modelos críticos, auditoría completa con LogsActivity, Activity Log mejorado, InventoryCount en Inventario, y análisis de mejoras pendientes.

---

## Proposed Changes

### 1. SoftDeletes en Modelos Críticos

Agregar `SoftDeletes` + migración `deleted_at` a modelos que tienen registros hijos y riesgo de FK violation.

#### [MODIFY] [Warehouse.php](file:///home/fernando/Desktop/Terracota/PHP%20Sistema/app/Models/Warehouse.php)
Agregar `use SoftDeletes;` — tiene compras, stocks, conteos, movimientos

#### [MODIFY] [Branch.php](file:///home/fernando/Desktop/Terracota/PHP%20Sistema/app/Models/Branch.php)
Agregar `use SoftDeletes;` — tiene ventas, compras, almacenes, cajas

#### [MODIFY] [Category.php](file:///home/fernando/Desktop/Terracota/PHP%20Sistema/app/Models/Category.php)
Agregar `use SoftDeletes;` — tiene productos y subcategorías

#### [MODIFY] [CashRegister.php](file:///home/fernando/Desktop/Terracota/PHP%20Sistema/app/Models/CashRegister.php)
Agregar `use SoftDeletes;` — tiene ventas y movimientos de caja

#### [MODIFY] [ExpenseCategory.php](file:///home/fernando/Desktop/Terracota/PHP%20Sistema/app/Models/ExpenseCategory.php)
Agregar `use SoftDeletes;` — tiene gastos (con cascade que borra los gastos!)

#### [NEW] Migración `add_soft_deletes_to_critical_models`
Agrega `deleted_at` a: `warehouses`, `branches`, `categories`, `cash_registers`, `expense_categories`

---

### 2. LogsActivity en Todos los Modelos Relevantes

Modelos que **ya tienen** LogsActivity: Sale, Purchase, Product, Customer, Supplier, InventoryAdjustment, User (7 total)

Agregar LogsActivity a los siguientes **8 modelos adicionales**:

| Modelo | log_name | Justificación |
|---|---|---|
| **Warehouse** | `almacen` | Cambios en almacenes son críticos para trazabilidad |
| **Branch** | `sucursal` | Cambios en estructura organizacional |
| **Category** | `categoria` | Reorganización de productos |
| **CashRegister** | `caja` | Apertura/cierre de caja, montos |
| **Expense** | `gasto` | Registro de gastos financieros |
| **ExpenseCategory** | `categoria_gasto` | Cambios en categorización de gastos |
| **InventoryCount** | `conteo_inventario` | Tomas físicas son auditoría pura |
| **Stock** | `stock` | Todo cambio de stock debe quedar registrado |

---

### 3. ActivityResource Mejorado

#### [MODIFY] [ActivityResource.php](file:///home/fernando/Desktop/Terracota/PHP%20Sistema/app/Filament/Resources/ActivityResource.php)

Mejoras:
- Expandir filtro de módulos con los 15 log_names (los 7 existentes + 8 nuevos)
- Traducir tipo de modelo (`subject_type`) a español en la tabla
- Columna "Modelo" visible por defecto con nombre legible
- Agregar filtro por rango de fechas para auditorías
- Agregar filtro por usuario (quién hizo el cambio)
- Vista detallada con diff visual (valores anteriores → nuevos) mejorada

---

### 4. InventoryCountResource → Inventario + Traducciones

#### [MODIFY] [InventoryCountResource.php](file:///home/fernando/Desktop/Terracota/PHP%20Sistema/app/Filament/Resources/InventoryCountResource.php)

- Agregar `$navigationGroup = 'Inventario'`
- Agregar `$modelLabel = 'Conteo de Inventario'`
- Agregar `$pluralModelLabel = 'Conteos de Inventario'`
- Icono descriptivo: `heroicon-o-clipboard-document-check`
- Agregar TrashedFilter + RestoreAction (ya tiene SoftDeletes)

---

### 5. Warehouse/Category/CashRegister/ExpenseCategory Resources — TrashedFilter

Agregar a cada resource:
- `TrashedFilter` en filtros
- `RestoreAction` en acciones de tabla
- `RestoreBulkAction` en bulk actions
- Modificar `getEloquentQuery()` para incluir `withoutGlobalScopes`

---

## Mejoras Pendientes Consolidadas (de todos los planes)

> [!IMPORTANT]
> Las siguientes mejoras están documentadas pero **aún no implementadas**.

### Alta Prioridad
- [ ] Eager loading optimizado en Resources (evitar N+1)
- [ ] Atajos de teclado en POS (F2, F4, ESC, F6)
- [ ] Pagos múltiples combinados en una misma venta
- [ ] Exportar reportes a Excel (`maatwebsite/excel`)

### Media Prioridad
- [ ] Etiquetas QR por ubicación
- [ ] Feedback visual al escanear barcode (flash verde/rojo)
- [ ] Carrito persistente en localStorage  
- [ ] Dashboard personalizable por rol
- [ ] Notificaciones en tiempo real (stock crítico)
- [ ] Opciones B2B/B2C (precios con/sin IVA)
- [ ] Botón "Pago Exacto" en POS

### Baja Prioridad (Fase 5+)
- [ ] Facturación electrónica SIFEN Paraguay
- [ ] Testing automatizado (PHPUnit/Pest)
- [ ] 2FA para admins
- [ ] API REST para app móvil
- [ ] Multi-tenant SaaS
- [ ] CI/CD con GitHub Actions

---

## Verification Plan

### Automated
- Intentar borrar un almacén con compras → debe hacer soft delete
- Verificar que `activity_log` registra cambios en los 15 modelos
- Verificar que InventoryCount aparece en menú Inventario

### Manual
- Revisar Activity Log con los filtros nuevos en el panel
- Confirmar que las traducciones se ven correctamente

# 🚨 Errores del Sistema — Guía de Referencia

Documentación de los errores más comunes que pueden aparecer en el sistema Terracota POS, sus causas y soluciones.

> **Nota:** A partir de esta versión, el sistema muestra mensajes amigables en español cuando ocurren estos errores, en lugar de la pantalla de error técnica.

---

## 1. "No se puede eliminar este registro" (Foreign Key Constraint)

### Qué significa
Estás intentando borrar un registro que tiene **datos relacionados** en otras tablas. La base de datos protege la integridad de los datos y no permite dejár "huérfanos" registros que dependen de este.

### Escenarios más comunes

| Intentás borrar... | Falla porque tiene... |
|---|---|
| **Almacén** | Compras, stocks, movimientos de stock, conteos de inventario |
| **Sucursal** | Ventas, compras, almacenes, cajas registradoras |
| **Categoría** | Productos asignados a esa categoría |
| **Usuario** | Ventas registradas, compras, cajas abiertas |
| **Categoría de Gasto** | Gastos registrados con esa categoría |
| **Proveedor** | Compras asociadas a ese proveedor |
| **Cliente** | Ventas asociadas a ese cliente |
| **Producto** | Variantes, stocks, items de venta/compra |

### Soluciones
1. **Desactivar en lugar de borrar** — Muchos modelos tienen campo `active`. Ponerlo en `false` oculta el registro sin romper relaciones.
2. **Reasignar** — Mover los registros dependientes a otro registro padre antes de eliminar.
3. **Verificar dependencias** — Antes de borrar, revisar si hay compras/ventas/stocks que referencien ese registro.

### Modelos protegidos con SoftDeletes (borrado lógico)
Los siguientes modelos ya usan borrado lógico (no se borran realmente de la BD):
- ✅ Venta (`Sale`)
- ✅ Compra (`Purchase`)
- ✅ Producto (`Product`)
- ✅ Proveedor (`Supplier`)
- ✅ Cliente (`Customer`)
- ✅ Gasto (`Expense`)
- ✅ Conteo de Inventario (`InventoryCount`)
- ✅ Pago de Cliente (`CustomerPayment`)
- ✅ Almacén (`Warehouse`) — *Agregado sesión 5*
- ✅ Sucursal (`Branch`) — *Agregado sesión 5*
- ✅ Categoría (`Category`) — *Agregado sesión 5*
- ✅ Caja Registradora (`CashRegister`) — *Agregado sesión 5*
- ✅ Categoría de Gasto (`ExpenseCategory`) — *Agregado sesión 5*

### Modelos SIN SoftDeletes (borrado físico — riesgo bajo)
- ⚠️ Stock (`Stock`) — registro operativo, depende del padre
- ⚠️ Comprobante (`Receipt`) — se borra con la venta
- ⚠️ Plantilla (`ReceiptTemplate`) — configuración, fácil de recrear

---

## 2. "Registro duplicado" (Unique Constraint)

### Qué significa
Estás intentando crear o editar un registro con un valor que ya existe en otro registro, y el sistema no permite duplicados en ese campo.

### Escenarios comunes
- Crear una **plantilla de impresión** con un tipo que ya existe (solo puede haber una plantilla activa por tipo)
- Registrar un **producto** con un barcode/SKU que ya está en uso
- Crear un **usuario** con un email que ya existe

### Solución
Cambiar el valor del campo duplicado o editar el registro existente en lugar de crear uno nuevo.

---

## 3. "Campo obligatorio vacío" (NOT NULL Constraint)

### Qué significa
Se intentó guardar un registro sin llenar un campo que es obligatorio en la base de datos.

### Escenarios comunes
- Guardar una venta sin seleccionar sucursal o vendedor
- Crear un producto sin nombre
- Registrar una compra sin proveedor

### Solución
Completar todos los campos obligatorios antes de guardar.

---

## 4. "Base de datos ocupada" (Database Locked — SQLite)

### Qué significa
SQLite solo permite una escritura a la vez. Si dos operaciones intentan escribir simultáneamente, una de ellas falla con este error.

### Cuándo ocurre
- Dos usuarios guardando al mismo tiempo
- El proceso de migraciones corriendo mientras el servidor responde requests
- Operaciones pesadas de seeding mientras se usa el sistema

### Solución
Esperar unos segundos e intentar de nuevo. Si persiste:
- Verificar que no haya migraciones u operaciones de consola corriendo
- Reiniciar el servidor con `php artisan serve`
- En producción, considerar migrar a **PostgreSQL o MySQL** para soporte de escrituras concurrentes

---

## 5. Otros errores posibles

### Error al imprimir comprobante (directorio inexistente)
- **Causa:** La carpeta `storage/app/receipts/` no existía
- **Estado:** ✅ Corregido — El sistema ahora crea la carpeta automáticamente

### Error al cargar logo en factura
- **Causa:** No se ejecutó `php artisan storage:link` o el logo no está en `storage/app/public/`
- **Solución:** Ejecutar `php artisan storage:link` y verificar que el logo esté subido correctamente

### Pantalla en blanco al generar PDF
- **Causa:** Variables faltantes en la vista Blade (ej: `$company` es null)
- **Solución:** Asegurarse de que existe al menos una empresa (`Company`) en el sistema

### Error de autenticación al abrir caja
- **Causa:** El usuario no tiene una caja abierta asignada
- **Solución:** Abrir una caja registradora antes de intentar registrar ventas

---

## Mapa de Relaciones Críticas (FK)

```
Company ──→ Branch ──→ Warehouse ──→ Stock
                  │           │         └──→ StockMovement
                  │           └──→ Purchase ──→ PurchaseItem
                  │           └──→ InventoryCount ──→ InventoryCountItem
                  │
                  └──→ Sale ──→ SaleItem
                  │        └──→ Payment
                  │        └──→ Receipt
                  │
                  └──→ CashRegister ──→ CashMovement

Category ──→ Product ──→ ProductVariant ──→ SaleItem
                                       └──→ PurchaseItem
                                       └──→ Stock

Supplier ──→ Purchase
Customer ──→ Sale ──→ CustomerPayment
User ──→ Sale, Purchase, CashRegister, Expense
ExpenseCategory ──→ Expense
```

---

## Implementación Técnica

El manejo global de errores está en `app/Providers/AppServiceProvider.php` en el método `configureFilamentExceptionHandling()`. Intercepta `QueryException` en rutas de Livewire/Filament y muestra notificaciones amigables al usuario con:

- **Título** descriptivo del tipo de error
- **Cuerpo** con explicación y sugerencia de solución
- **Nivel** de severidad (danger, warning)
- **Persistente** — no desaparece automáticamente

Se traduce automáticamente el nombre de las tablas y campos al español.

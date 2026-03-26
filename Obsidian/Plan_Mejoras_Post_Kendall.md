# Plan de Mejoras Post Análisis Kendall & Kendall

**Fecha:** 2026-03-26
**Estado:** En progreso

## Contexto

Tras completar las fases F1–F6 del Plan_Kendall_Kendall.md, se identificaron brechas funcionales y técnicas adicionales mediante revisión del código fuente. Este plan detalla las mejoras priorizadas.

## Hallazgos de la revisión

| Área | Estado real en código | Acción |
|------|----------------------|--------|
| InventoryAdjustment → stock real | ✅ Ya implementado (afterSave/afterCreate) | — |
| StockResource solo lectura | ✅ Ya implementado (canCreate/Edit/Delete = false) | — |
| defaultSort en resources | ⚠️ Solo 11/18 resources lo tienen | Implementar |
| QR SIFEN en PDF facturas | ⚠️ SifenQrService existe pero no se imprime | Implementar |
| Firma digital SIFEN (RSA-SHA256) | ❌ Pendiente | Futura fase |
| Pagos múltiples por venta | ❌ No implementado | Implementar |
| Feature test ajuste de inventario | ❌ No existe | Implementar |

---

## Tareas

### T1 — defaultSort en todos los Resources ✅
**Prioridad:** Baja / Impacto: Consistencia UX

Resources sin `defaultSort`:
- `LocationResource` → `name asc`
- `WarehouseResource` → `name asc`
- `CategoryResource` → `name asc`
- `ExpenseCategoryResource` → `name asc`
- `SupplierResource` → `created_at desc`
- `CustomerResource` → `created_at desc`
- `ProductResource` → `created_at desc`
- `UserResource` → `created_at desc`
- `CashRegisterResource` → `created_at desc`

**Enfoque:** Agregar `->defaultSort(...)` en el método `table()` de cada Resource.

---

### T2 — QR SIFEN en PDF de facturas ✅
**Prioridad:** Media / Impacto: Cumplimiento SET Paraguay

`SifenQrService::buildUrl()` ya genera la URL del QR. Falta:
1. Invocar el servicio al generar el PDF de comprobante
2. Agregar `<img>` con QR usando un generador (ej. `simple-qrcode` o URL de API pública)
3. Actualizar blade template del PDF

**Enfoque:** Usar `endroid/qr-code` o `simplesoftwareio/simple-qrcode` para generar imagen base64 del QR e insertarla en la vista Blade del PDF.

---

### T3 — Pagos múltiples por venta
**Prioridad:** Media / Impacto: Operativa cajero

Actualmente `SaleService::createSale()` registra un solo `Payment`. La mejora permite dividir el cobro entre múltiples métodos (ej. parte contado + parte tarjeta).

**Enfoque:**
1. El formulario de venta acepta un array de `payments[]` con `method` y `amount`
2. `SaleService` itera y crea múltiples `Payment` records
3. Validar que la suma sea >= al total de la venta

---

### T4 — Feature test: flujo ajuste de inventario
**Prioridad:** Media / Impacto: Calidad / Regresión

Cubrir:
- Crear ajuste en estado `pending` → stock NO cambia
- Aprobar ajuste → stock se actualiza vía `processAdjustment()`
- Idempotencia: aprobar dos veces no duplica movimientos

---

### T5 — Firma digital SIFEN (RSA-SHA256) [Futura fase]
**Prioridad:** Alta / Impacto: Cumplimiento legal

Requiere certificado `.p12` de la SET + `robrichards/xmlseclibs`.
**Bloqueado por:** Obtención del certificado digital de la empresa.

---

## Checklist de implementación

- [x] T1: defaultSort en 9 resources
- [x] T2: QR en PDF facturas (`endroid/qr-code`, SVG base64 en `ReceiptService`)
- [x] T3: Pagos múltiples por venta (Repeater `payments` en `SaleResource` form)
- [x] T4: Feature test ajuste inventario (5 tests, 11 assertions)
- [ ] T5: Firma digital SIFEN (pendiente certificado)

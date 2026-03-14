# Plan de Implementación: Plantillas Editables e Impresión de Compras

## Resumen
El usuario solicitó dos funcionalidades principales:
1. Poder modificar el formato de los comprobantes (notas de pedido, facturas, compras) desde el sistema.
2. Poder imprimir los comprobantes de Compras a proveedores.

## Enfoque Técnico

### 1. Plantillas Editables (Receipt Templates)
Para permitir que los formatos se modifiquen desde el sistema, sacaremos el código HTML/Blade fijo y lo guardaremos en una tabla de base de datos.
- **Crear Modelo y Migración:** `ReceiptTemplate` (`name`, `type`, `content_html`, `is_active`).
  - `type` puede ser: `sale_ticket`, `sale_invoice`, `purchase_ticket`, etc.
  - `content_html` almacenará el código con variables que pueden ser reemplazadas en tiempo de impresión (ej. `{{ $sale->id }}`, `{{ $sale->total }}`).
- **Crear Recurso en Filament:** `ReceiptTemplateResource` para que el administrador pueda editar este HTML directamente desde el panel usando un editor de código o área de texto avanzada.
- **Modificar `ReceiptService`:** Hacer que busque primero si existe una plantilla activa en `ReceiptTemplate` para el tipo solicitado. Si existe, renderizamos ese string (usando Blade inline o parseando los tags). Si no existe, usamos los archivos `.blade.php` por defecto (fallback).

### 2. Impresión de Compras
Actualmente solo se imprimen `Sales` (Ventas). Hay que habilitarlo para `Purchases`.
- Añadir la relación `receipts()` en el modelo `Purchase`.
- Añadir el botón `Imprimir` en el `PurchaseResource`, replicando el comportamiento de `SaleResource`.
- En `ReceiptService`, permitir que la entidad a imprimir pueda ser una instancia de `Sale` o de `Purchase`, unificando su comportamiento (posiblemente a través de un contrato o simplemente detectando la clase).

## Lista de Tareas
- [x] MIGRACIÓN Y MODELO: Crear `ReceiptTemplate`.
- [x] FILAMENT: Crear `ReceiptTemplateResource`.
- [x] BACKEND: Refactorizar `ReceiptService` para utilizar la tabla `ReceiptTemplate` y soportar `Purchase`.
- [x] BACKEND: Añadir relaciones en modelo `Purchase`.
- [x] UI: Añadir botón Imprimir a `PurchaseResource` y vista por defecto de compra (`resources/views/pdf/purchase_ticket.blade.php`).

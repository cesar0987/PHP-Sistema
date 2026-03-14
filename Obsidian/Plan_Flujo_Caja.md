# Plan: Flujo de Caja y Restricciones de Modificación (Ventas)

## Descripción
El objetivo es implementar un control estricto de Caja (Cash Register) en el Punto de Venta (POS) del sistema, de modo que toda venta requiera de una caja *abierta* para poder procesarse. Adicionalmente, se establecerán reportes detallados del cajero, control de permisos sobre modificaciones de facturas y mejoras en el flujo para hacerlo manejable.

## Enfoque Técnico (Technical Approach)

### 1. Control de Flujo de Caja Obligatoria
- **Intercepción de Ventas:** En `SaleResource` (CreateSale y ListSales), verificar si el vendedor conectado tiene una Caja (`CashRegister`) en estado `abierta`.
- **Bloqueo Inteligente:** Si no tiene caja, bloquear el botón de "Crear Venta" y mostrar una alerta redirigiendo a abrir caja. Al hacer la venta, vincular automáticamente el `cash_register_id`.
- **Acciones de Caja (Apertura / Cierre):** En `CashRegisterResource`, implementar acciones directas de "Abrir Caja" (definiendo monto inicial) y "Cerrar Caja" (declarando monto físico y calculando faltantes/sobrantes).

### 2. Reporte de Caja (Resumen y Detalle de Facturas)
- **InfoList / View Page de Caja:** Crear una página detallada de vista (`ViewCashRegister`) en Filament.
- **Relaciones Manager:** Añadir un `RelationManager` para mostrar todas las Ventas (Facturas y Tickets) realizadas en esa caja.
- **Widgets de Resumen (Stats):** En el Header de la Caja, colocar Widgets que resuman: Monto Apertura, Total Vendido (desglosando Facturas vs Tickets), Total Ingreso y Monto Esperado de Cierre.

### 3. Restricciones de Modificación (Policies)
- **Bloqueo a Vendedores:** Modificar `SalePolicy` para que solo los usuarios con el rol `admin` (super_admin, admin) puedan editar o eliminar Ventas (`update`, `delete`), especialmente si el documento es Factura SIFEN. El vendedor común solo puede `create` o `view`.

### 4. Sugerencias de Mejoras para el Flujo
- **Arqueo y Cierre Ciego:** Que el sistema no diga al cajero cuánto *debe* tener, sino que el cajero diga cuánto *ve* físicamente al cerrar, e internamente calcule la diferencia de arqueo.
- **Impresión de Cierre:** Añadir la opción de imprimir el ticket de Reporte "Z" (cierre de turno).

## Checklist de Tareas
- [ ] Implementar Policy de Venta (`SalePolicy`) restringiendo `update` solo a Admins.
- [ ] Configurar flujo en `SaleResource` (Validar caja abierta y asignar caja automáticamente a la venta).
- [ ] Refactorizar `CashRegisterResource` con acciones de Abrir/Cerrar y Status reactivo.
- [ ] Construir la View Page de CashRegister con resúmenes estadísticos y listado detallado de facturas.
- [ ] Actualizar documentación global del sistema.

# Sugerencias de Mejora para Ferretería POS (Basado en Odoo 19)

Fecha: 2026-03-14
Sistema Base: Laravel + Filament

En base al análisis exhaustivo de la documentación y características del **Punto de Venta de Odoo 19**, a continuación presento un plan estratégico de mejoras y funcionalidades recomendadas para el POS de la ferretería que podrían potenciar enormemente su eficiencia y escalabilidad.

---

## 1. Flexibilidad de Actualización de Inventario
**Inspiración Odoo:** Odoo permite elegir si el stock se descuenta en "Tiempo Real" (Instantáneo) o al "Cierre de Sesión" (Session/Shift end).
- **Problema actual:** El sistema actual descuenta stock de inmediato al aprobar una venta, lo que puede causar cuellos de botella en la base de datos durante momentos de altísima concurrencia o ventas masivas (ej. eventos de descuento).
- **Mejora estructurada:** Implementar **"Sesiones de Caja"**. Permitir que las ventas se agrupen y el stock físico se recalcule definitivamente al hacer el "Corte de Caja" diario, o bien utilizar *background jobs* (Jobs de Laravel) para actualizar el stock sin congelar la pantalla del vendedor.

## 2. Pagos Múltiples y "One-Click Payment"
**Inspiración Odoo:** Pagos fraccionados (split payments) ágiles y bypass de la pantalla de pago para métodos exactos (One-click payment).
- **Mejora:** En la pantalla de `SaleResource`, permitir que el cliente pague una misma factura usando múltiples métodos combinados (Ej: 50% Efectivo, 50% Transferencia Bancaria). Esto requiere modificar la relación de Ventas a `hasMany(Payment::class)`. Actualmente existe el modelo `Payment`, pero la UI de creación de venta en Filament debe soportar un _repeater_ para múltiples métodos de pago, sumando un validador que asegure que el monto total pagado coincida con el subtotal.
- **Botón "Pago Exacto":** Un botón rápido en el POS que asuma que el cliente pagó en efectivo con el monto exacto, saltándose el modal de vuelto y de confirmación y agilizando la fila de cobro.

## 3. Facturas Modernas con Códigos QR Ecológicos
**Inspiración Odoo:** Recibos modernos y menos papel, códigos QR embebidos.
- **Mejora:** Mejorar el PDF de los tickets generados por el `ReceiptService` para que incluyan:
  1. Un Código QR que el cliente pueda escanear para descargar su garantía o verificar la autenticidad fiscal de la factura (E-kuatia).
  2. Opción en el POS: **"Enviar por Email o WhatsApp en lugar de imprimir"**, reduciendo los costos de papel en la ferretería.

## 4. Opciones de Visualización de Precios (B2B vs B2C)
**Inspiración Odoo:** Opción dinámica de mostrar precios con impuesto incluido o excluido.
- **Mejora:** Al ser una ferretería, es muy común trabajar tanto con clientes minoristas (Consumidor final) como empresas (B2B). Se debe añadir un *Switch* en la interfaz de Venta que permita alterar la visualización de los precios del catálogo interactivo: "Precios con IVA incluido" o "Precios sin IVA", para facilitar la cotización a constructores y empresas.

---

### Siguientes Pasos
Si este plan es aprobado, sugiero comenzar por el **punto 2 (Pagos Múltiples)** y el **punto 4 (Visualización B2B/B2C)**, dado que son los que mayor retorno inmediato de eficiencia otorgarán a los vendedores en el mostrador.

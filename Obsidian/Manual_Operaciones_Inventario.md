# Manual de Operaciones: Módulo de Inventario

Esta guía explica a nivel técnico y operativo cómo funciona la gestión de Stock en este Sistema POS.

## 1. Regla de Oro del Inventario
**El sistema está diseñado bajo Arquitectura Limpia.** Esto significa que **el stock JAMÁS se edita a mano** cambiando un número en la vista de `Stock`.
Si un producto tiene `0` de stock, o no aparece en la tabla de stock, **está bien**. El sistema requiere que el inventario se modifique mediante *transacciones reales* que dejen rastro y puedan ser auditadas por la gerencia.

## 2. ¿Cómo ingreso mercadería al sistema?
Existen dos formas principales:
- **Módulo de Compras:** Cuando le compras mercadería a un Proveedor, al registrar la compra y darle a "Recibir Productos", el sistema automáticamente:
  1. Aumentará el stock disponible en la sucursal seleccionada.
  2. Dejará un registro contable de la compra.
  3. Dejará un registro en el historial de **Movimientos de Stock** (indicando que la causa fue una compra).

- **Ajustes de Inventario:** Si estás migrando al sistema por primera vez (Inventario Inicial) o encontraste mercadería perdida/sobrante, debes ir a **Ajustes de Inventario**.
  1. Creas un nuevo Ajuste, seleccionando la sucursal y el motivo (ej. "Inventario Inicial 2024" o "Mercadería encontrada en fondo").
  2. Agregas en la lista los productos y la cantidad real que contaste físicamente.
  3. Lo guardas con el estado **Aprobado**.
  4. El sistema actualizará el stock a la cantidad introducida y creará un historial para la auditoría indicando quién lo aprobó.

## 3. ¿Cómo sale la mercadería?
- **Ventas (POS):** Es el flujo natural. Al aprobar y cobrar una venta, el sistema descuenta automáticamente las unidades de la sucursal indicada y no permite vender por debajo del límite a menos que sea una sobreventa controlada.
- **Ajustes por Merma / Robo:** Nuevamente, vas a **Ajustes de Inventario**, ingresas los productos afectados, y en "Nuevo Stock" pones la cantidad menor que confirmaste. El sistema calculará la diferencia negativa y registrará la merma.

## 4. ¿Por qué la vista "Stock" es de solo lectura?
La vista `Stock` que encuentras en el menú es simplemente un "Visualizador" (visor o reporte rápido). Evita que los usuarios manipulen cantidades directas (que podrían ocultar robos) imponiendo que todas las modificaciones dejen un registro indeleble en la actividad de la base de datos.

## Resumen de Tablas
- `stocks`: Guarda la fotografía actual del stock (Cantidades vigentes en el momento).
- `stock_movements`: El Libro Diario del almacén. Guarda qué subió y bajó el stock, cuándo y por qué (compras, ventas o ajustes).
- `inventory_adjustments`: Archivos de expedientes de por qué se cambió el stock a mano.

# Manual de Operaciones: Carga de Stock Inicial

Este manual describe el flujo de trabajo para ingresar productos que ya tienes físicamente en tu inventario al sistema Terracota POS.

## 1. Cargar el Producto (Ficha Técnica)
El primer paso es registrar la información general del producto.
1. Ve al módulo **Productos**.
2. Haz clic en **Nuevo Producto**.
3. Completa los datos: Nombre, Categoría, Marca, IVA (10%, 5% o Exenta).
4. En la sección de **Variantes**, define el SKU (Código), Código de Barras y el **Precio de Venta**.
5. Guarda el producto. 
   - *Nota: En este paso el stock seguirá siendo 0.*

## 2. Cargar el Inventario (Cantidad Física)
Una vez que el producto existe en el sistema, debes decirle cuántas unidades hay y en qué depósito.
1. Ve al módulo **Ajustes de Inventario**.
2. Haz clic en **Nuevo Ajuste**.
3. Selecciona el **Almacén** (Depósito) donde está el producto.
4. Elige el tipo de ajuste: **"Adición"** o **"Apertura de Inventario"**.
5. En la sección de productos, busca el que creaste antes e ingresa la **cantidad** que tienes físicamente.
6. Guarda el ajuste.

## 3. Verificar el Stock
1. Puedes ir al módulo **Stock** para ver el consolidado de tus existencias por depósito.
2. Si realizas una venta, el sistema descontará automáticamente de este inventario cargado.

---
**Recomendación:** Para compras futuras de mercadería a proveedores, utiliza siempre el módulo de **Compras**, ya que este registra el costo del producto y la deuda con el proveedor automáticamente.

# Plan: Fix Purchase Receipt Generation

## Issue
Al intentar imprimir un Ticket de Compra, el sistema arroja el error `no such column: receipts.purchase_id`. Esto se debe a que la tabla `receipts` originalmente fue diseñada solo para `sale_id`.

## Technical Approach
1. **Database Schema:** Crear una migración que agregue el campo `purchase_id` a la tabla `receipts` de forma segura (nullable, foreign key). *Nota: Esto ya fue ejecutado pero parece haber problemas de caché o persistencia.*
2. **Models:** Actualizar el modelo `Receipt` para incluir `purchase_id` en los campos conectables (`$fillable`) y añadir la relación `purchase()`.
3. **Services:** Modificar `ReceiptService` para que, al generar un comprobante para una compra, asigne correctamente el `purchase_id`.
4. **Cache/Locks:** Limpiar la caché de esquema de Laravel y verificar que no existan bloqueos de SQLite que impidan leer la nueva columna. SQLite puede cachear esquemas en conexiones de larga duración como `php artisan serve`.

## Tasks
- [x] Crear documento de plan (este archivo).
- [ ] Limpiar caché de la aplicación y base de datos para asegurar lectura correcta de columnas.
- [ ] Comprobar que el error haya desaparecido tras la limpieza de caché.
- [ ] Notificar al usuario.

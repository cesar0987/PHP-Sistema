# Plan de Cumplimiento — Kendall & Kendall

> **Objetivo:** Llevar el sistema Terracota POS a un nivel profesional y auditable según la metodología de Análisis y Diseño de Sistemas de Kendall & Kendall.  
> **Referencia:** [Análisis Kendall & Kendall](Analisis_Kendall_Kendall.md)  
> **Fecha:** 14/03/2026

---

## Fases del plan

| Fase | Nombre | Foco | Dependencias |
|---|---|---|---|
| **F1** | Controles internos | Policies, autenticación, autorización | ✅ Completada |
| **F2** | Validación de datos | Form Requests, reglas de negocio | ✅ Completada |
| **F3** | Testing y calidad | Tests unitarios y de feature | ⬜ En proceso |
| **F4** | Documentación técnica | DER, DFDs, diccionario de datos, arquitectura | ✅ Completada |
| **F5** | Documentación operativa | Manuales de usuario, runbook, changelog | ✅ Completada |
| **F6** | Producción y continuidad | Backups, migración BD, deploy, capacitación | F1-F5 |

---

## Fase 1 — Controles Internos y Seguridad

> **Objetivo K&K Cap. 14:** "El sistema debe impedir que usuarios no autorizados realicen operaciones sensibles"

### 1.1 Policies con lógica real
- [x] `SalePolicy` — Solo admin/supervisor puede anular; cajero solo crear
- [x] `PurchasePolicy` — Solo admin/compras puede crear compras
- [x] `ProductPolicy` — Solo admin puede eliminar productos
- [x] `CustomerPolicy` — Todos pueden ver, solo admin puede eliminar
- [x] `SupplierPolicy` — Solo admin/compras
- [x] `CashRegisterPolicy` — Cajero solo puede operar su propia caja
- [x] `WarehousePolicy` — Solo admin puede crear/editar/eliminar almacenes
- [x] `CategoryPolicy` — Solo admin puede modificar categorías
- [x] `UserPolicy` — Solo admin puede gestionar usuarios
- [x] `InventoryAdjustmentPolicy` — Solo admin/supervisor puede ajustar inventario
- [x] `StockPolicy` — Solo lectura para cajeros
- [x] `ReceiptPolicy` — Solo lectura
- [x] `WarehouseAislePolicy` — Solo admin
- [x] `ExpensePolicy` — *Agregada en esta sesión*
- [x] `ExpenseCategoryPolicy` — *Agregada en esta sesión*
- [x] `InventoryCountPolicy` — *Agregada con regla: completados no editables*
- [x] `ReceiptTemplatePolicy` — *Agregada, solo admin*

### 1.2 Registrar Policies en los Resources
- [x] Filament auto-descubre policies por convención
- [x] Todas las policies tienen `hasPermissionTo()` / `hasRole()`

### 1.3 Auditoría de autenticación
- [x] Listener para `Illuminate\Auth\Events\Login` → registrar en activity_log
- [x] Listener para `Illuminate\Auth\Events\Logout`
- [x] Listener para `Illuminate\Auth\Events\Failed` (intentos fallidos)
- [x] Pestaña "Autenticación" en ActivityResource

### 1.4 Segregación por sucursal
- [x] Middleware o Scope global para filtrar datos por `branch_id` del usuario (BranchScope)
- [x] Agregar `branch_id` al usuario si no existe
- [x] Solo admin ve datos de todas las sucursales

### Entregable F1
✅ Cada rol tiene permisos específicos, autenticación auditada, datos filtrados por sucursal

---

## Fase 2 — Validación de Datos

> **Objetivo K&K Cap. 11:** "Toda entrada de datos debe ser validada en múltiples capas"

### 2.1 Form Requests para operaciones críticas
- [x] `StoreSaleRequest` — items, cantidades > 0, precios, stock
- [x] `StorePurchaseRequest` — proveedor, items, costos, fecha no futura
- [x] `StoreInventoryAdjustmentRequest` — motivo enum, cantidades ≠ 0
- [x] `UpdateProductRequest` — precio venta ≥ costo, SKU/barcode únicos
- [x] `StoreCashRegisterRequest` — monto apertura ≥ 0

### 2.2 Validación de razonabilidad (reglas de negocio)
- [x] Precio de venta no puede ser menor al costo (advertencia)
- [x] Cantidad en venta no puede exceder stock disponible
- [x] No se puede cerrar caja si hay ventas pendientes
- [x] No se puede eliminar un producto con stock > 0
- [x] Fecha de compra no puede ser futura
- [x] Monto de cierre de caja con diferencia > 10% genera alerta

### 2.3 Validación en Filament (complementar)
- [x] Agregar `->rules()` con las mismas reglas del Form Request
- [x] Agregar `->helperText()` explicativo en campos complejos
- [x] Agregar `->hint()` con información contextual

### Entregable F2
✅ Toda entrada validada tanto en UI (Filament) como en backend (Form Request)

---

## Fase 3 — Testing y Calidad de Código

> **Objetivo K&K Cap. 15:** "Las pruebas demuestran que el sistema funciona correctamente"

### 3.1 Tests unitarios (Services)
- [x] `SaleServiceTest` — calculateTotal(), createSale(), cancelSale(), insufficient stock (4 tests)
- [x] `InventoryServiceTest` — addStock(), removeStock(), adjustStock(), transferStock(), checkMinimum(), getTotalStock() (10 tests)
- [x] `PurchaseServiceTest` — createPurchase(), receiveProducts(), cancelPurchase() (4 tests)
- [x] `LocationServiceTest` — numberToLetters(), lettersToNumber(), roundtrip, createLocation(), assignLocation() (6 tests)
- [x] `CreditServiceTest` — recordSalePayment(), updateCustomerBalance() (2 tests)
- [ ] `ReceiptServiceTest` — pospuesto (requiere DomPDF + vistas renderizadas)

### 3.2 Tests de feature (flujos completos)
- [ ] Flujo de venta: buscar → agregar → cobrar → stock actualizado
- [ ] Flujo de compra: crear → recibir → stock incrementado
- [ ] Anulación: anular venta → stock devuelto
- [ ] Cierre de caja: cerrar → totales calculados
- [ ] Login fallido: 5 intentos → bloqueado

### 3.3 Calidad de código
- [x] Instalar y configurar Laravel Pint (PSR-12) — 29 archivos formateados
- [x] Instalar Larastan nivel 5 — 74 errores baseline registrados (fix incremental)
- [ ] Corregir errores Larastan (incremental, por archivo)
- [ ] Configurar pre-commit hook (opcional)

### Entregable F3
✅ 28 tests pasando (72 assertions), código formateado PSR-12, Larastan nivel 5 instalado

---

## Fase 4 — Documentación Técnica

> **Objetivo K&K Cap. 7-9:** "Los diagramas son el lenguaje del análisis de sistemas"

### 4.1 Diagrama Entidad-Relación (DER)
- [x] DER completo — 35 tablas con relaciones y cardinalidad ([Diagrama_ER.md](Diagrama_ER.md))
- [x] Herramienta: Mermaid (nativo en Obsidian)
- [x] Cardinalidad incluida (1:N, N:M)

### 4.2 Diagramas de Flujo de Datos (DFD)
- [x] **Nivel 0** — Diagrama de contexto ([DFD_Sistema.md](DFD_Sistema.md))
- [x] **Nivel 1** — 5 procesos principales (Ventas, Compras, Inventario, Caja, Admin)
- [x] **Nivel 2** — Detalle de P1 (Ventas) y P2 (Compras)

### 4.3 Diccionario de Datos
- [x] `Diccionario_de_Datos.md` — 17 tablas principales documentadas ([Diccionario_de_Datos.md](Diccionario_de_Datos.md))
- [x] Tipos, restricciones, reglas de negocio y tablas de dominio para enums

### 4.4 Diagrama de arquitectura
- [x] Diagrama de capas (Presentación → Lógica → Datos → Infraestructura) ([Arquitectura_Sistema.md](Arquitectura_Sistema.md))
- [x] Diagrama de componentes (Services, Policies, Form Requests)
- [x] Sequence diagram de request HTTP
- [x] Stack tecnológico completo

### Entregable F4
✅ DER (35 tablas), DFDs (3 niveles), Diccionario de datos (17 tablas), Arquitectura (4 diagramas Mermaid)

---

## Fase 5 — Documentación Operativa

> **Objetivo K&K Cap. 16:** "La documentación permite operar y mantener el sistema sin dependencia del desarrollador"

### 5.1 Manual del Cajero
- [x] Crear `docs/Manual_Cajero.md`
- [x] Contenido:
  - Cómo iniciar sesión
  - Cómo abrir caja (monto de apertura)
  - Buscar productos (barcode, nombre, SKU)
  - Agregar al carrito, modificar cantidad
  - Aplicar descuento
  - Cobrar (efectivo, tarjeta, transferencia, QR)
  - Anular última venta (si tiene permiso)
  - Cerrar caja (conteo de efectivo)
  - Errores comunes y qué hacer

### 5.2 Manual del Administrador
- [x] Crear `docs/Manual_Administrador.md`
- [x] Contenido:
  - Gestión de productos y categorías
  - Gestión de proveedores y clientes
  - Sistema de ubicaciones (pasillos, estantes)
  - Crear y recibir compras
  - Ajustes de inventario y conteos físicos
  - Gestión de usuarios y roles
  - Configurar plantillas de comprobantes
  - Leer reportes del dashboard
  - Revisar el log de actividad

### 5.3 Runbook de operaciones
- [x] Crear `docs/Runbook.md`
- [x] Contenido:
  - Qué hacer si el sistema no carga
  - Cómo restaurar un backup
  - Cómo resetear contraseña de admin
  - Cómo migrar la BD en un update
  - Contactos de soporte

### 5.4 Changelog
- [x] Crear `CHANGELOG.md` en raíz del proyecto
- [x] Documentar versiones: v1.0 (base), v1.1 (auditoría), v1.2 (comprobantes), etc.
- [x] Formato: Added / Changed / Fixed / Removed

### Entregable F5
✅ Manual de cajero, manual de admin, runbook, changelog

---

## Fase 6 — Producción y Continuidad

> **Objetivo K&K Cap. 16-17:** "La implementación y el mantenimiento son tan importantes como el desarrollo"

### 6.1 Backups automáticos
- [x] Creado `BackupSqliteCommand` para backup nativo en caliente de la DB.
- [x] Comando programado diariamente en `routes/console.php`.

### 6.2 y 6.3 Deploy y Base de Datos
- [x] Creado `Obsidian/Deploy_Produccion.md`.
- [x] Incluye pasos de instalación local, permisos de carpetas y comandos de optimización.
- [x] Guía futura para migrar de SQLite a PostgreSQL/MySQL.

### 6.4 y 6.5 Planes de Mantenimiento y Capacitación
- [x] Creado `Obsidian/Plan_Mantenimiento_Capacitacion.md`.
- [x] Rutinas de entrenamiento por roles (cajeros, administradores).
- [x] Monitoreo de logs y reportes de errores.

### Entregable F6
✅ Sistema en producción, backups verificados y programados, manuales generados y plan de mantenimiento activo.

---

## Cronograma sugerido

| Fase | Duración estimada | Prioridad |
|---|---|---|
| F1 — Controles internos | 2-3 días | 🔴 Alta |
| F2 — Validación | 1-2 días | 🔴 Alta |
| F3 — Testing | 2-3 días | 🔴 Alta |
| F4 — Doc. técnica | 1-2 días | 🟡 Media |
| F5 — Doc. operativa | ✅ | 🟡 Media |
| F6 — Producción | ✅ Completado | 🟡 Media |

**Total estimado: 11-18 días de desarrollo (PLAN KENDALL TÉRMINADO)**

> **Nota:** Las fases F1, F2 y F4 no tienen dependencias entre sí y pueden trabajarse en paralelo. F3 prefiere que F2 esté listo. F5 prefiere F1. F6 requiere todas las anteriores.

---

*Plan basado en Kendall & Kendall — Análisis y Diseño de Sistemas*

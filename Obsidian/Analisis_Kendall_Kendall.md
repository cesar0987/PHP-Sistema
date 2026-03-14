# Análisis del Sistema según Kendall & Kendall

> **Referencia:** Análisis y Diseño de Sistemas — Kendall & Kendall  
> **Sistema:** Terracota POS + Inventario  
> **Fecha:** 14/03/2026

---

## Resumen Ejecutivo

El sistema cubre bien las fases 1-4 del SDLC (Análisis, Diseño, Desarrollo, Implementación). Las principales brechas están en: **testing**, **validación formal**, **documentación de usuario**, **control de acceso granular**, y **backup/recuperación**.

| Área K&K | Estado | Nota |
|---|---|---|
| Ciclo de vida (SDLC) | ✅ Parcial | Fases 1-4 cubiertas, falta fase 5 (mantenimiento formal) |
| Requerimientos | ✅ Documentados | En Obsidian, falta diagrama de casos de uso |
| Modelos de datos (E-R) | ✅ Implementado | ~45 tablas, relaciones correctas, falta diagrama visual |
| DFD (Flujo de datos) | ⚠️ Falta | No hay diagramas de flujo documentados |
| Diseño de E/S | ✅ Bueno | Formularios Filament, PDFs, POS funcional |
| Validación de datos | ✅ Implementado | Form Requests + Filament, validación multi-capa |
| Controles internos | ✅ Implementado | 17 Policies con lógica real, auth auditada |
| Calidad (Testing) | ✅ Implementado | 20 tests (18 unitarios + 2 feature), 39 assertions |
| Documentación | ⚠️ Parcial | Técnica sí, operativa no (sin manual de usuario) |
| Seguridad | ✅ Bueno | Auditoría, SoftDeletes, encriptación, rate limiting |

---

## 1. Requerimientos y Modelado (Cap. 2-7)

### ✅ Lo que tenés bien
- Requerimientos documentados en Obsidian con fases claras
- Modelos de datos completos con relaciones

### ⚠️ Lo que falta

**Diagrama Entidad-Relación visual (DER)**
- K&K enfatiza que todo sistema debe tener un DER actualizado
- Tenés la estructura en texto pero no un diagrama visual
- **Acción:** Crear DER con herramienta como dbdiagram.io o DrawSQL y guardarlo en `docs/erd.png`

**Diagramas de Flujo de Datos (DFD)**
- K&K dedica capítulos enteros a DFDs — son fundamentales
- Necesitás al menos nivel 0 (diagrama de contexto) y nivel 1
- **Acción:** Documentar los flujos principales:
  - Flujo de venta (cliente → POS → stock → comprobante)
  - Flujo de compra (proveedor → recepción → stock)
  - Flujo de caja (apertura → ventas → cierre)

**Diccionario de Datos**
- K&K lo considera esencial para todo sistema
- Cada tabla/campo debería tener descripción, tipo, restricciones, y reglas de negocio
- **Acción:** Crear `Obsidian/Diccionario_de_Datos.md` con todas las tablas y sus campos documentados

---

## 2. Diseño de Entrada/Salida (Cap. 11-12)

### ✅ Lo que tenés bien
- Formularios Filament con labels en español
- PDFs generados (ticket 80mm, factura A4)
- POS con escáner de código de barras
- Badges de estado con colores semánticos

### ⚠️ Lo que falta

**Validación de rango y tipo (complementar)**
- Algunas reglas de razonabilidad aún pueden mejorarse
- **Acción:** Revisar que todos los Form Requests tengan reglas de razonabilidad completas

**Mensajes de error contextuales**
- ✅ Ya implementado con el handler global de errores
- Mejorable: agregar tooltips o hints en campos complejos

---

## 3. Controles Internos y Seguridad (Cap. 14)

### ✅ Lo que tenés bien
- Auditoría con Spatie ActivityLog (15 modelos)
- SoftDeletes en 13 modelos (protección contra borrado accidental)
- Encriptación de datos sensibles (RUC)
- Rate limiting en endpoints
- Sesiones con expiración
- ✅ **17 Policies con lógica real** — `SalePolicy`, `PurchasePolicy`, `ProductPolicy`, `CustomerPolicy`, `SupplierPolicy`, `CashRegisterPolicy`, `WarehousePolicy`, `CategoryPolicy`, `UserPolicy`, `InventoryAdjustmentPolicy`, `StockPolicy`, `ReceiptPolicy`, `WarehouseAislePolicy`, `ExpensePolicy`, `ExpenseCategoryPolicy`, `InventoryCountPolicy`, `ReceiptTemplatePolicy`
- ✅ **Auditoría de autenticación** — Listeners para Login, Logout y Failed con pestaña en ActivityResource

### ⚠️ Lo que falta

**Segregación de funciones**
- K&K enfatiza: quien registra no debería aprobar
- **Acción:** Implementar flujo de aprobación para anulaciones > X monto

**Control de acceso por sucursal**
- Si tenés multi-sucursal, un cajero de sucursal A no debería ver datos de sucursal B
- **Acción:** Filtrar queries por `branch_id` del usuario logueado (Scope global + middleware)

---

## 4. Calidad y Testing (Cap. 15)

### ✅ Estado actual: 20 tests, 39 assertions — Todo PASA

> Última ejecución: 14/03/2026 — `OK (20 tests, 39 assertions)` en 0.806s

| Suite | Tests | Servicio cubierto |
|---|---|---|
| `InventoryServiceTest` | 10 | `addStock`, `removeStock`, `adjustStock`, `transferStock`, `checkMinimum`, `getTotalStock` |
| `SaleServiceTest` | 4 | `calculateTotal`, `createSale`, `cancelSale`, stock insuficiente |
| `PurchaseServiceTest` | 4 | `createPurchase`, `receiveProducts`, `cancelPurchase` |
| `ExampleTest (Unit)` | 1 | Sanidad PHPUnit |
| `ExampleTest (Feature)` | 1 | Middleware auth (redirect 302) |

**Tests pendientes (prioridad media):**

| Test | Tipo | Prioridad |
|---|---|---|
| `LocationServiceTest` — `numberToLetters()` | Unit | 🟡 Media |
| `ReceiptServiceTest` — `generatePdf()` | Unit | 🟡 Media |
| Flujo completo de venta (POS → stock → comprobante) | Feature | 🟡 Media |
| Cierre de caja calcula totales | Feature | 🟡 Media |
| Login fallido: 5 intentos → bloqueado | Feature | 🟡 Media |

---

## 5. Documentación del Sistema (Cap. 16)

### ✅ Lo que tenés
- README.md con setup
- PHPDoc en 5 Services
- Documentación técnica en Obsidian
- Guía de errores del sistema

### ⚠️ Lo que falta según K&K

**Manual de usuario**
- K&K: "Todo sistema debe tener un manual que permita al usuario operar sin asistencia"
- No tenés manual de cajero ni de administrador
- **Acción:** Crear:
  - `docs/Manual_Cajero.md` — Cómo abrir caja, buscar productos, cobrar, anular, cerrar caja
  - `docs/Manual_Administrador.md` — Gestión de productos, reportes, usuarios, configuración

**Manual de operaciones (Runbook)**
- Qué hacer si el sistema se cae, cómo restaurar backup, contactos
- **Acción:** Crear `docs/Runbook.md`

**Changelog formal**
- K&K recomienda documentar cada versión del sistema
- **Acción:** Crear `CHANGELOG.md` con formato semántico (Added/Changed/Fixed)

**Diagrama de arquitectura**
- Cómo se conectan los componentes (Laravel ↔ Filament ↔ Livewire ↔ SQLite)
- **Acción:** Crear diagrama de arquitectura en `docs/architecture.md`

---

## 6. Implementación y Mantenimiento (Cap. 16-17)

### ⚠️ Lo que falta

**Plan de migración a producción**
- K&K describe estrategias: directa, paralela, piloto, por fases
- No hay documentación de cómo deployar el sistema
- **Acción:** Documentar proceso de deploy (servidor, BD, backups)

**Plan de respaldo y recuperación**
- K&K: "¿qué pasa si se pierde la base de datos?"
- SQLite es un archivo — si se corrompe, se pierde todo
- **Acción:**
  - Configurar `spatie/laravel-backup` para backups diarios
  - Documentar proceso de restauración
  - Para producción: migrar a PostgreSQL

**Plan de capacitación**
- K&K: todo sistema necesita un plan de capacitación para usuarios
- **Acción:** Crear guía de capacitación o video tutorial del POS

---

## 7. Interfaz de Usuario (Cap. 13)

### ✅ Lo que tenés bien
- Diseño consistente (Filament v3)
- Modo oscuro
- Labels en español
- Badges con colores semánticos
- Notificaciones amigables

### ⚠️ Mejoras según K&K

**Feedback para el usuario**
- K&K: "El usuario debe saber siempre en qué estado está el sistema"
- Falta: indicador de carga en operaciones largas (generar PDF)
- Falta: confirmaciones visuales al guardar (✅ "Producto guardado")

**Consistencia**
- Algunos Resources tienen `navigationGroup` y otros no
- Algunos modelos en español, otros en inglés en la BD
- **Acción:** Auditar que todos los Resources tengan grupo y labels traducidos

**Ayuda contextual**
- K&K recomienda ayuda en línea en cada pantalla
- **Acción:** Agregar `->helperText()` en campos complejos de formularios

---

## Resumen de Acciones Prioritarias

| # | Acción | Estado | Impacto | Esfuerzo |
|---|---|---|---|---|
| 1 | ~~Implementar Policies con lógica real~~ | ✅ Hecho (17 policies) | 🔴 Alto | Medio |
| 2 | ~~Crear 4+ tests unitarios críticos~~ | ✅ Hecho (20 tests) | 🔴 Alto | Medio |
| 3 | ~~Form Requests para validación backend~~ | ✅ Hecho (5 requests) | 🔴 Alto | Bajo |
| 4 | **Manual de usuario (cajero + admin)** | ⬜ Pendiente | 🟡 Medio | Medio |
| 5 | **Diagrama E-R visual** | ⬜ Pendiente | 🟡 Medio | Bajo |
| 6 | **DFDs nivel 0 y 1** | ⬜ Pendiente | 🟡 Medio | Medio |
| 7 | **Backups automáticos** | ⬜ Pendiente | 🔴 Alto | Bajo |
| 8 | ~~Registrar login/logout en auditoría~~ | ✅ Hecho (3 listeners) | 🟡 Medio | Bajo |
| 9 | **Diccionario de datos** | ⬜ Pendiente | 🟡 Medio | Medio |
| 10 | **CHANGELOG.md** | ⬜ Pendiente | 🟢 Bajo | Bajo |

---

> **Conclusión (actualizada 14/03/2026):** El sistema ha avanzado significativamente. Las brechas de **testing** (ahora 20 tests), **autorización** (17 policies con lógica real), **validación backend** (5 Form Requests), y **auditoría de auth** (3 listeners) han sido **resueltas**. Las brechas restantes se concentran en **documentación** (manuales, DER, DFDs, diccionario de datos) y **preparación para producción** (backups, deploy). El sistema pasó de "funcional" a "auditable"; falta llevarlo a "documentado y listo para producción".

---

*Análisis basado en Kendall & Kendall — Análisis y Diseño de Sistemas*

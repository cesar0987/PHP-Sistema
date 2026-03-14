# Plan de Mejoras: Finanzas e Inventario (Inspirado en Odoo)

## Feature Name
Módulos de Cuentas por Cobrar (Créditos a Clientes) y Gestión de Gastos Operativos.

## Description
Para facilitar el crecimiento de la ferretería y transicionar hacia un control contable más estricto, implementaremos dos módulos vitales extraídos de las mejores prácticas de ERPs como Odoo:
1. **Cuentas por Cobrar (Accounts Receivable):** Permitirá vender a crédito a clientes de confianza, llevando un estado de cuenta, límite de crédito y registro de pagos parciales o totales.
2. **Gastos (Expenses):** Un registro simple de salidas de dinero que no son compras de mercadería (ej. Luz, Agua, Salarios, Fletes), para poder calcular la rentabilidad real del negocio.

## Technical Approach (Architecture Rules Applied)
De acuerdo a `.cursor/rules/architecture.md`, mantendremos la lógica de negocio fuera de los controladores/recursos y la encapsularemos en `Services`.

### 1. Cuentas por Cobrar (Créditos)
* **Modelos:** 
  * Modificar `Customer` añadiendo `credit_limit` y `current_balance` (o calcularlo al vuelo).
  * Crear modelo `AccountReceivable` (o `CustomerCredit`) enlazado a `Sale`.
  * Crear modelo `Payment` (o `CreditPayment`) para registrar las entregas de dinero.
* **Flujo (Service Layer):** Al procesar una Venta con tipo de pago "Crédito", el `SaleService` (o nuevo `CreditService`) generará la deuda automáticamente.
* **UI Filament:** Un nuevo Resource `AccountReceivableResource` o simplemente pestañas dentro de `CustomerResource` (RelationManager) para ver su Estado de Cuenta y un botón Action para "Registrar Pago".

### 2. Gestión de Gastos (Expenses)
* **Modelos:** Crear modelo `Expense` y `ExpenseCategory`.
* **Columnas de Expense:** `date`, `amount`, `category_id`, `description`, `receipt_number`, `user_id`.
* **UI Filament:** `ExpenseResource` sencillo para carga rápida, con un widget de sumatoria mensual en el Dashboard.

### 3. Futuro (Unidades de Medida - UoM)
* *Nota:* Se deja como Phase 2 de esta planificación. Requerirá refactorizar sustancialmente cómo se guarda el stock y costeo actual.

## Tasks Checklist
- [x] Crear documento de planificación (este archivo).
- [ ] Solicitar aprobación del usuario para la estructura de la base de datos propuesta.
- [ ] **Módulo Gastos (Expenses):**
  - [ ] Crear migraciones y modelos `ExpenseCategory` y `Expense`.
  - [ ] Crear `ExpenseResource` en Filament.
- [ ] **Módulo Cuentas por Cobrar:**
  - [ ] Añadir `payment_method` (Contado/Crédito) a la tabla `sales`.
  - [ ] Crear modelo y migración `CustomerPayment` (id, customer_id, sale_id_nullable, amount, date, method).
  - [ ] Implementar `CreditService` para centralizar lógica de saldo.
  - [ ] Modificar `CustomerResource` para mostrar saldos y permitir cobros.
  - [ ] Actualizar el POS (`SaleResource`) para manejar ventas a crédito.

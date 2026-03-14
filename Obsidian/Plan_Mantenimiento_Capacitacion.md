# Plan de Mantenimiento y Capacitación

Como parte de la entrega de la **Fase 6**, se debe garantizar la continuidad operativa de Terracota POS mediante la capacitación formal y rutinas preventivas.

---

## 1. Plan de Capacitación

Para un correcto inicio de operaciones (Go-Live), se proponen las siguientes sesiones:

### Fase A: Equipo Administrativo (Supervisores y Dueños)
- **Duración Ideal:** 2 Horas
- **Temas a cubrir:**
  - Estructura de Catálogos Corporativos (Empresa, Sucursales, Categorías).
  - Creación de Usuarios y asignación de Roles por Sucursal.
  - Gestión de Proveedores e ingreso de mercadería a Almacenes (Compras).
  - Interpretación del Cierre de Caja y revisión de Auditorías (Activity Log).
  - Emisión de Reportes Gerenciales y KPIs en el Dashboard.

### Fase B: Equipo Operativo (Cajeros / Vendedores)
- **Duración Ideal:** 1 Hora
- **Modalidad:** Taller práctico ("Role play" de ventas).
- **Temas a cubrir:**
  - Login y consideraciones del filtro por Sucursal (`BranchScope`).
  - Procedimiento de apertura diaria de Caja (Ingreso de efectivo base).
  - Flujo de Venta al Contado (Búsqueda por código de barras, confirmación de stock).
  - Flujo de Venta a Crédito y consulta de límite de cliente.
  - Proceso de rendición y Arqueo / Cierre de caja.
  - Consulta de Stocks.

---

## 2. Plan de Mantenimiento Preventivo (SLA)

### Operaciones Diarias (Automatizadas)
- **Backup de Base de Datos**: Ejecutado todos los días a las 23:00 mediante el comando `php artisan app:backup-sqlite`. Guarda una copia reteniendo siempre los últimos 7 días.

### Tareas Mensuales
- **Revisión de Logs de Auditoría**: Un supervisor debe filtrar en el `ActivityLog` acciones de tipo *Delete* (borrados) o anulaciones de compras/ventas para detectar anomalías.
- **Chequeo de Espacio en Servidor**: Las copias de seguridad de SQLite y los logs de la aplicación de Laravel (`storage/logs/laravel.log`) crecen con el tiempo. Comandos útiles: `df -h`.
- **Rotación de Logs de Laravel**: Verificar si el `.env` está configurado con `LOG_CHANNEL=daily` para que en Laravel no pese un solo archivo `laravel.log`.

### Proceso de Reporte de Bugs y Mejoras
Si los usuarios detectan un comportamiento inesperado o necesitan una mejora:
1. **Nivel 1 (Usuario a Supervisor):** El cajero lo reporta a su superior inmediato.
2. **Nivel 2 (Supervisor a Soporte IT):** El supervisor evalúa y envía ticket a TI incluyendo:
   - Captura de pantalla del error.
   - Hora aproximada del suceso.
   - Sucursal y equipo.
3. **Nivel 3 (Soporte TI):** Se evalúa la viabilidad e ingresa a una "Lista de Backlog" (Fase de Mantenimiento / Evolutivo K&K).

# Terracota POS - Sistema Integral de Punto de Venta y Gestión

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)
![Filament](https://img.shields.io/badge/Filament-v3-EAB308?style=flat-square&logo=filament)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php)
![SQLite/PostgreSQL](https://img.shields.io/badge/Database-SQLite%20%7C%20PostgreSQL-336791?style=flat-square&logo=postgresql)

**Terracota POS** es una plataforma moderna de alta fiabilidad diseñada para la administración de comercios físicos (como ferreterías, tiendas de retail, etc.). Proporciona una gestión estricta y trazable de inventario, cajas registradoras, manejo multi-sucursal, y facturación electrónica (SIFEN - Paraguay).

---

## 🚀 Características Principales

### 🛒 TPV (Punto de Venta) / Central de Ventas
- Interfaz enfocada en la rapidez utilizando Filament Forms y Livewire.
- Cajas diarias individuales por usuario (Apertura y Cierre con arqueo ciego o transparente).
- **Múltiples métodos de pago integrados:** Efectivo, Tarjeta de Crédito, Tarjeta de Débito, Transferencia, Cheque y Código QR.
- **Ventas a Crédito:** Gestión de clientes con límites de crédito autorizados y calendario de vencimiento de cuotas (Credit Due Dates).
- Generación y guardado histórico completo de Notas de Pedido (Budgets).

### 📦 Gestión de Inventario Multi-Sucursal y Lotes (FIFO)
- Organización por **Empresas > Sucursales > Almacenes** con ubicaciones dinámicas.
- **Control de Vencimiento (FIFO):** Gestión de mercadería perecedera mediante **Lotes de Stock (`StockBatch`)**.
- Descuento inteligente: El sistema prioriza automáticamente la salida de productos con fecha de vencimiento más próxima.
- Compras a proveedores, Recepciones Parciales y Transferencias con trazabilidad total de lotes.

### 🇵🇾 Facturación Electrónica Paraguaya (SIFEN v150)
- Soporte para emisión de recibos y facturas legales con integración nativa SIFEN.
- Generación de **CDC (Código de Control)** y códigos QR oficiales para cumplimiento tributario.

### 🛡️ Seguridad y Mantenimiento
- **Backups desde la Interfaz:** Copias de seguridad de la base de datos y archivos directamente desde el panel.
- **Configuración Dinámica:** Ajuste de parámetros del sistema y frecuencias de backup desde el módulo de Ajustes Generales.
- **Auditoría:** Registro de transacciones con `Spatie Activitylog` y permisos por roles.

---

## 🏛️ Arquitectura del Software (Clean Architecture)

Este proyecto aplica principios de **Clean Architecture** priorizando una clara separación de responsabilidades para favorecer la escalabilidad y las pruebas unitarias:

- **Entities (Models):** Clases puras de Eloquent (Ej: `ProductVariant`, `Sale`). Se extrajo cualquier *N+1 query* y cálculos de negocio de los *accessors* (como totales de stock) delegándolos a Domain Services.
- **Use Cases (Services Layer):** El core del negocio vive aquí (`SaleService`, `InventoryService`, `ReceiptService`). Estos servicios no dependen del framework (por ejemplo, nunca se usa `auth()->id()` o dependencias HTTP directamente dentro de ellos).
- **Interface Adapters (Controllers & Filament Resources):** Proveen la conversión HTTP → Negocio. Filament actúa como la UI y la capa de Presentación primaria.
- **Dependency Injection:** En lugar de `app(Service::class)` o "Service Locators" mágicos, todas las inyecciones se pasan de forma declarativa y por constructores.

---

## ⚙️ Requisitos del Sistema

- **PHP** >= 8.3
- **Composer** v2+
- **Node.js** y **NPM** (Para compilar assets front-end en caso de personalizaciones)
- Extensión **PHP-GD** o **Imagick** (Requerida por Endroid QR Code y DomPDF).
- Base de datos relacional (Para desarrollo: SQLite; Producción: PostgreSQL >= 14 o MySQL >= 8)

---

## 🛠️ Instalación Rápida

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/tu-usuario/terracota-pos.git
   cd terracota-pos
   ```

2. **Instalar dependencias:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Configurar el Entorno:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *No olvides configurar tu base de datos en el archivo `.env` o dejar SQLite para pruebas inmediatas.*

4. **Migraciones y Poblar Datos:**
   ```bash
   php artisan migrate --seed
   ```
   *(El flag `--seed` inicializa los Roles de sistema, Permisos de UI, Un usuario Admin de prueba, y parámetros básicos de facturación).*

5. **Lanzar el Servidor en Producción/Desarrollo:**
   ```bash
   php artisan serve
   ```

---

## 👤 Credenciales de Acceso Local (Seeding)

Tras correr los seeders (`DatabaseSeeder`), se creará una base fundacional con los siguientes datos de acceso general:

- **Panel:** `http://localhost:8000/admin`
- **Usuario Admin:** `admin@terracota.local` (o equivalente definido en sus seeds)
- **Contraseña:** `password`

---

## 📂 Estructura de Directorios Clave

```text
app/
├── Filament/          # Paneles, Formularios, Tablas e Interacciones de la UI
│   ├── Resources/     # CRUDs visuales principales (Ventas, Clientes, Productos)
│   ├── Widgets/       # Componentes analíticos del Dashboard
│   └── Pages/         # Vistas con interfaces a medida (Ejem: Calendario de Créditos)
├── Models/            # Entidades y Definición de Relaciones (ORM)
└── Services/          # El Núcleo de Lógica del Negocio [CAPA DE DOMINIO]
    ├── SaleService.php       # Gestión de creación, aprobación y anulación de ventas
    ├── InventoryService.php  # Movimiento y auditoría de inventario (Kardex)
    ├── PurchaseService.php   # Órdenes de compras y recepción
    ├── ReceiptService.php    # Generación y ensamblado de Tickets y Facturas
    └── Sifen/                # Lógica de Facturación SET (Cálculos CDC, XML, etc.)
tests/
├── Unit/              # Pruebas sin dependencias estructurales intensas (Probanza de Servicios)
└── Feature/           # Pruebas de integración HTTP / Controladores
```

---

## 🧪 Pruebas Unitarias y Funcionales

Terracota cuenta con un suite robusto de pruebas que aseguran la consistencia de los módulos críticos, en especial los Servicios Financieros y de Inventario:

```bash
# Ejecutar toda la batería de test
php artisan test

# Solo ejecutar pruebas de facturación o stock
php artisan test --filter SaleServiceTest
php artisan test --filter InventoryServiceTest
```

---

## 📜 Licencia y Autoría

Desarrollado específicamente para infraestructuras multi-cruzada orientadas a resultados rápidos de mostrador.
El proyecto está construido bajo licencia [MIT](https://opensource.org/licenses/MIT). Las capas gráficas usan librerías licenciadas bajo la suite open source de Laravel y Filament V3.

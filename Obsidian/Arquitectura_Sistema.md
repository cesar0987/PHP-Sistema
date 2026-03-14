# Arquitectura del Sistema — POS Ferretería

> **Fecha:** 14/03/2026  
> **Metodología:** Kendall & Kendall — Capítulo 9  
> **Referencia:** [Plan Kendall & Kendall — Fase 4](Plan_Kendall_Kendall.md)

---

## Diagrama de Capas

```mermaid
graph TB
    subgraph PRESENTACION["🖥️ Capa de Presentación"]
        BROWSER["Navegador Web"]
        FILAMENT["Filament v3\n(Panel Admin)"]
        LIVEWIRE["Livewire\n(Componentes reactivos)"]
        PDF_VIEW["Vistas Blade\n(tickets PDF)"]
    end

    subgraph LOGICA["⚙️ Capa de Lógica de Negocio"]
        SERVICES["Services\nInventoryService\nSaleService\nPurchaseService\nReceiptService\nLocationService\nCreditService"]
        POLICIES["Policies (17)\nControl de acceso\npor rol"]
        REQUESTS["Form Requests (5)\nValidación backend"]
        OBSERVERS["Observers\n(ActivityLog, SoftDeletes)"]
    end

    subgraph DATOS["🗃️ Capa de Datos"]
        MODELS["Eloquent Models\n(~35 modelos)"]
        MIGRATIONS["Migrations\n(esquema BD)"]
        SEEDERS["Seeders\n(datos iniciales)"]
        SQLITE[("SQLite\n:memory: en tests\n/storage/app en producción")]
    end

    subgraph INFRAESTRUCTURA["🔧 Infraestructura"]
        LARAVEL["Laravel 11.x\n(Framework PHP)"]
        SPATIE_PERM["spatie/laravel-permission\n(Roles y permisos)"]
        SPATIE_ACTIVITY["spatie/laravel-activitylog\n(Auditoría)"]
        DOMPDF["barryvdh/laravel-dompdf\n(Generación PDF)"]
        PINT["Laravel Pint\n(Formato PSR-12)"]
        LARASTAN["Larastan\n(Análisis estático)"]
    end

    BROWSER --> FILAMENT
    FILAMENT --> LIVEWIRE
    FILAMENT --> PDF_VIEW
    LIVEWIRE --> SERVICES
    PDF_VIEW --> DOMPDF

    SERVICES --> MODELS
    SERVICES --> POLICIES
    REQUESTS --> SERVICES
    OBSERVERS --> MODELS

    MODELS --> SQLITE
    MIGRATIONS --> SQLITE
    SEEDERS --> SQLITE

    LARAVEL --> FILAMENT
    LARAVEL --> LIVEWIRE
    LARAVEL --> SERVICES
    LARAVEL --> MODELS
    SPATIE_PERM --> POLICIES
    SPATIE_ACTIVITY --> OBSERVERS
```

---

## Diagrama de Componentes

```mermaid
graph LR
    subgraph ENTRADA["Entrada"]
        UI_POS["POS Filament\n(venta presencial)"]
        UI_ADMIN["Panel Admin\n(CRUD completo)"]
        UI_REPORTS["Dashboard\n(reportes)"]
    end

    subgraph NUCLEO["Núcleo"]
        INV["InventoryService\nstock + movimientos"]
        SALE["SaleService\nventas + cobros"]
        PURCH["PurchaseService\ncompras + recepción"]
        RECEIPT["ReceiptService\nPDF comprobantes"]
        LOC["LocationService\nubicaciones almacén"]
        CREDIT["CreditService\ncuenta corriente"]
    end

    subgraph SALIDA["Salida"]
        TICKET["🖨️ Ticket 80mm"]
        FACTURA["📄 Factura A4"]
        REPORTE["📊 Reportes"]
        LOG_AUDIT["📋 Log Auditoría"]
    end

    UI_POS --> SALE
    UI_POS --> INV
    UI_ADMIN --> PURCH
    UI_ADMIN --> LOC
    UI_ADMIN --> CREDIT
    UI_REPORTS --> REPORTE

    SALE --> RECEIPT
    SALE --> INV
    PURCH --> INV
    PURCH --> RECEIPT
    SALE --> CREDIT

    RECEIPT --> TICKET
    RECEIPT --> FACTURA
    INV --> LOG_AUDIT
    SALE --> LOG_AUDIT
    PURCH --> LOG_AUDIT
```

---

## Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| **Lenguaje** | PHP | 8.3 |
| **Framework** | Laravel | 11.x |
| **Panel Admin** | Filament | v3 |
| **Frontend reactivo** | Livewire | v3 |
| **Base de datos** | SQLite | — |
| **ORM** | Eloquent | (incluido en Laravel) |
| **Roles/Permisos** | spatie/laravel-permission | ^6 |
| **Auditoría** | spatie/laravel-activitylog | ^4 |
| **PDF** | barryvdh/laravel-dompdf | ^3 |
| **Formato código** | Laravel Pint | ^1.24 |
| **Análisis estático** | Larastan | ^3.0 |
| **Tests** | PHPUnit | ^11.5 |

---

## Flujo de Request HTTP

```mermaid
sequenceDiagram
    participant Browser
    participant Filament
    participant Policy
    participant Service
    participant Model
    participant DB

    Browser->>Filament: HTTP Request (autenticado)
    Filament->>Policy: ¿Tiene permiso?
    Policy-->>Filament: ✅ Autorizado / ❌ Forbidden
    Filament->>Service: Ejecutar operación
    Service->>Model: Leer/Escribir datos
    Model->>DB: SQL Query (Eloquent)
    DB-->>Model: Resultados
    Model-->>Service: Colección / Instancia
    Service-->>Filament: Resultado
    Filament-->>Browser: Response (HTML/JSON/PDF)
```

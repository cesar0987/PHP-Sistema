---
description: Laravel 12 + Filament v3 specific conventions for this project
alwaysApply: true
scope: laravel
---

# Laravel & Filament Conventions

## Modelos Eloquent

### Estructura Estándar
```php
#[ScopedBy([BranchScope::class])]  // Si pertenece a una sucursal
class MyModel extends Model
{
    use LogsActivity;      // Siempre
    use SoftDeletes;       // En modelos de negocio

    protected $fillable = [...];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([...])      // Solo campos relevantes
            ->logOnlyDirty()
            ->useLogName('nombre_log')
            ->setDescriptionForEvent(fn (string $eventName) => "...");
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',  // Siempre decimal:2 para montos
            'active' => 'boolean',
            'date_field' => 'date',
            'datetime_field' => 'datetime',
        ];
    }

    // Relaciones con tipos explícitos
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
```

### Reglas de Modelos
- **`$fillable` siempre explícito** — nunca usar `$guarded = []`.
- **`casts()` en método**, no en `$casts` propiedad (Laravel 12+).
- **Montos monetarios** siempre en `decimal:2`.
- **Relaciones** con `BelongsTo`, `HasMany`, `HasOne`, etc. con tipos de retorno.
- **`@property` docblock** para autocompletado: mantenerlo sincronizado con `$fillable`.

## Migraciones

```php
// Convención de nombres: timestamp_description_table.php
2026_03_25_000000_add_sifen_columns_to_companies_table.php

// Siempre implementar down() correctamente
public function down(): void
{
    Schema::table('companies', function (Blueprint $table) {
        $table->dropColumn(['ruc_dv', 'tipo_contribuyente']);
    });
}
```

- Un concepto por migración (no mezclar tablas independientes).
- Usar `->nullable()->after('campo')` para agregar columnas a tablas existentes.
- Los índices van en la misma migración que la columna.

## Filament v3

### Forms
```php
Forms\Components\Section::make('Título')
    ->schema([
        Forms\Components\Grid::make(3)->schema([
            Forms\Components\TextInput::make('field')
                ->label('Label en español')
                ->required(),
        ]),
    ])
```

### Tables
```php
Tables\Columns\TextColumn::make('field')
    ->label('Label')
    ->sortable()
    ->searchable(),
```

### Acciones que usan servicios
```php
Tables\Actions\Action::make('cancelar')
    ->requiresConfirmation()
    ->action(function (Sale $record) {
        app(SaleService::class)->cancelSale($record);
        Notification::make()->success()->title('Venta cancelada')->send();
    }),
```

### Notificaciones
- Siempre usar `Filament\Notifications\Notification` para feedback al usuario.
- Mensajes de éxito en verde, errores en rojo, advertencias en amarillo.
- Mensajes siempre en **español**.

## Inyección de Dependencias
Usar `app(ServiceClass::class)` en Filament o constructor injection en controladores.

```php
// En Filament Resources/Pages
$service = app(SaleService::class);

// En Controllers (constructor injection)
public function __construct(private SaleService $saleService) {}
```

## Queries y Performance

- Usar `with()` para eager loading cuando se iteran relaciones.
- Evitar queries dentro de loops (N+1 problem).
- Usar `select()` para limitar columnas en listados grandes.

```php
// ✅ Correcto
Sale::with(['customer', 'items.productVariant.product'])->get();

// ❌ Incorrecto (N+1)
Sale::get()->each(fn ($s) => $s->customer->name);
```

## Validación
- Requests de validación en `app/Http/Requests/` para controladores HTTP.
- En Filament, la validación va en las definiciones del `form()`.
- Nunca validar en el servicio lo que ya se valida en el Request/Form.

## Variables de Entorno
- Acceder siempre con `config('key')`, nunca con `env('KEY')` fuera de `config/`.
- Documentar nuevas variables en `CLAUDE.md` y en `.env.example`.

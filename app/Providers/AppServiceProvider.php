<?php

namespace App\Providers;

use App\Listeners\AuthEventListener;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureFilamentExceptionHandling();
        $this->configureAuthListeners();
        $this->configureBackupDisk();
    }

    /**
     * Inyecta el disco de backup configurado en los ajustes generales
     * en la configuración global de Spatie Backup.
     */
    protected function configureBackupDisk(): void
    {
        try {
            // Solo intentamos cargar si no estamos en medio de una migración
            if (!app()->runningInConsole() || !str_contains(implode(' ', $_SERVER['argv'] ?? []), 'migrate')) {
                $settings = app(\App\Settings\GeneralSettings::class);
                config(['backup.backup.destination.disks' => [$settings->backup_disk]]);
            }
        } catch (\Exception $e) {
            // Fallback silencioso si la tabla no existe
        }
    }

    /**
     * Registrar listeners de autenticación para auditoría.
     */
    protected function configureAuthListeners(): void
    {
        $listener = new AuthEventListener;
        Event::listen(Login::class, [$listener, 'handleLogin']);
        Event::listen(Logout::class, [$listener, 'handleLogout']);
        Event::listen(Failed::class, [$listener, 'handleFailed']);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('pos', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Manejo global de excepciones de base de datos en Filament.
     *
     * Captura errores comunes (violaciones de FK, duplicados, etc.) y
     * los convierte en notificaciones amigables para el usuario en español.
     */
    protected function configureFilamentExceptionHandling(): void
    {
        $handler = app(ExceptionHandler::class);

        $handler->renderable(function (QueryException $e, Request $request) {
            // Solo interceptar en rutas del admin panel (Livewire/Filament)
            if (! $request->is('livewire/*') && ! $request->is('admin/*')) {
                return null;
            }

            $errorCode = $e->getCode();
            $message = $e->getMessage();

            // --- FOREIGN KEY CONSTRAINT (SQLSTATE 23000) ---
            if (str_contains($message, 'FOREIGN KEY constraint failed')
                || str_contains($message, 'Integrity constraint violation: 19 FOREIGN KEY')) {

                // Intentar detectar la tabla afectada desde el SQL
                $table = $this->extractTableFromSql($e->getSql());
                $friendlyTable = $this->getSpanishTableName($table);

                Notification::make()
                    ->title('No se puede eliminar este registro')
                    ->body(
                        "Este registro de **{$friendlyTable}** tiene datos relacionados en el sistema "
                        .'(compras, ventas, stocks, etc.) que dependen de él. '
                        ."\n\nPara eliminarlo, primero debés mover o eliminar los registros que lo referencian, "
                        .'o considerar desactivarlo en lugar de borrarlo.'
                    )
                    ->danger()
                    ->persistent()
                    ->send();

                return back();
            }

            // --- UNIQUE CONSTRAINT VIOLATION ---
            if (str_contains($message, 'UNIQUE constraint failed')
                || (is_string($errorCode) && $errorCode === '23000' && str_contains($message, 'Duplicate entry'))) {

                $field = $this->extractFieldFromUniqueError($message);
                $friendlyField = $this->getSpanishFieldName($field);

                Notification::make()
                    ->title('Registro duplicado')
                    ->body(
                        "Ya existe un registro con el mismo valor para **{$friendlyField}**. "
                        .'Por favor, usá un valor diferente.'
                    )
                    ->warning()
                    ->persistent()
                    ->send();

                return back();
            }

            // --- NOT NULL CONSTRAINT ---
            if (str_contains($message, 'NOT NULL constraint failed')) {
                $field = $this->extractFieldFromNotNullError($message);
                $friendlyField = $this->getSpanishFieldName($field);

                Notification::make()
                    ->title('Campo obligatorio vacío')
                    ->body("El campo **{$friendlyField}** es obligatorio y no puede estar vacío.")
                    ->warning()
                    ->persistent()
                    ->send();

                return back();
            }

            // --- DATABASE IS LOCKED (SQLite) ---
            if (str_contains($message, 'database is locked')) {
                Notification::make()
                    ->title('Base de datos ocupada')
                    ->body(
                        'La base de datos está temporalmente bloqueada por otra operación. '
                        .'Esperá unos segundos e intentá de nuevo.'
                    )
                    ->warning()
                    ->persistent()
                    ->send();

                return back();
            }

            // --- GENERIC DB ERROR (fallback) ---
            Notification::make()
                ->title('Error de base de datos')
                ->body(
                    'Ocurrió un error inesperado al procesar tu solicitud. '
                    ."Si el problema persiste, contactá al administrador del sistema.\n\n"
                    .'Detalle técnico: '.class_basename($e).' — '.Str::limit($message, 120)
                )
                ->danger()
                ->persistent()
                ->send();

            return back();
        });
    }

    /**
     * Extrae el nombre de la tabla desde la consulta SQL.
     */
    private function extractTableFromSql(string $sql): string
    {
        // DELETE FROM "table_name" ...
        if (preg_match('/(?:delete\s+from|insert\s+into|update)\s+["\']?(\w+)["\']?/i', $sql, $matches)) {
            return $matches[1];
        }

        return 'registro';
    }

    /**
     * Extrae el nombre del campo desde un error UNIQUE.
     */
    private function extractFieldFromUniqueError(string $message): string
    {
        // UNIQUE constraint failed: table.column
        if (preg_match('/UNIQUE constraint failed:\s*\w+\.(\w+)/i', $message, $matches)) {
            return $matches[1];
        }
        // Duplicate entry '...' for key '...'
        if (preg_match("/for key '([^']+)'/i", $message, $matches)) {
            return $matches[1];
        }

        return 'campo';
    }

    /**
     * Extrae el nombre del campo desde un error NOT NULL.
     */
    private function extractFieldFromNotNullError(string $message): string
    {
        if (preg_match('/NOT NULL constraint failed:\s*\w+\.(\w+)/i', $message, $matches)) {
            return $matches[1];
        }

        return 'campo';
    }

    /**
     * Traduce nombres de tablas a español para mensajes al usuario.
     */
    private function getSpanishTableName(string $table): string
    {
        return match ($table) {
            'warehouses' => 'Almacén',
            'branches' => 'Sucursal',
            'companies' => 'Empresa',
            'categories' => 'Categoría',
            'products' => 'Producto',
            'product_variants' => 'Variante de Producto',
            'suppliers' => 'Proveedor',
            'customers' => 'Cliente',
            'sales' => 'Venta',
            'sale_items' => 'Ítem de Venta',
            'purchases' => 'Compra',
            'purchase_items' => 'Ítem de Compra',
            'users' => 'Usuario',
            'cash_registers' => 'Caja',
            'stocks' => 'Stock',
            'stock_movements' => 'Movimiento de Stock',
            'receipts' => 'Comprobante',
            'receipt_templates' => 'Plantilla',
            'expenses' => 'Gasto',
            'expense_categories' => 'Categoría de Gasto',
            'inventory_adjustments' => 'Ajuste de Inventario',
            'inventory_counts' => 'Conteo de Inventario',
            'payments' => 'Pago',
            default => $table,
        };
    }

    /**
     * Traduce nombres de campos a español para mensajes al usuario.
     */
    private function getSpanishFieldName(string $field): string
    {
        return match ($field) {
            'name' => 'Nombre',
            'email' => 'Email',
            'ruc' => 'RUC',
            'document' => 'Documento',
            'phone' => 'Teléfono',
            'barcode' => 'Código de barras',
            'sku' => 'SKU',
            'type' => 'Tipo',
            'invoice_number' => 'Nro. Factura',
            'number' => 'Número',
            default => $field,
        };
    }
}

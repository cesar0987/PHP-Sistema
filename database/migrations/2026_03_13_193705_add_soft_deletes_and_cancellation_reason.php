<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega soft deletes a tablas principales y campo cancellation_reason a sales.
     */
    public function up(): void
    {
        // Soft deletes en tablas principales
        $tables = ['products', 'sales', 'purchases', 'customers', 'suppliers'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }

        // Campo de motivo de cancelación en ventas
        if (Schema::hasTable('sales') && ! Schema::hasColumn('sales', 'cancellation_reason')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->text('cancellation_reason')->nullable()->after('notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['products', 'sales', 'purchases', 'customers', 'suppliers'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropSoftDeletes();
                });
            }
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'cancellation_reason')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('cancellation_reason');
            });
        }
    }
};

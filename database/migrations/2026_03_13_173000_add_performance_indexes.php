<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('barcode');
            $table->index('sku');
            $table->index(['category_id', 'active']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('barcode');
            $table->index('sku');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('sale_date');
            $table->index('status');
            $table->index(['branch_id', 'sale_date']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('type');
            $table->index(['product_variant_id', 'warehouse_id']);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->index(['product_variant_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropIndex(['sku']);
            $table->dropIndex(['category_id', 'active']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropIndex(['sku']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sale_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['branch_id', 'sale_date']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['product_variant_id', 'warehouse_id']);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex(['product_variant_id', 'warehouse_id']);
        });
    }
};

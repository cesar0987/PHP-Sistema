<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_expiry')->default(false)->after('active');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('subtotal');
        });

        Schema::table('inventory_adjustment_items', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('quantity_after');
        });

        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            // Index for faster queries when ordering by expiry_date or filtering by positive quantity
            $table->index(['product_variant_id', 'warehouse_id', 'quantity']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');

        Schema::table('inventory_adjustment_items', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_expiry');
        });
    }
};

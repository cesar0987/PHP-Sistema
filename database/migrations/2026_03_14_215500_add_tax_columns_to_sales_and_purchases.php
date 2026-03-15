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
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('subtotal_exenta', 12, 2)->default(0)->after('subtotal');
            $table->decimal('subtotal_5', 12, 2)->default(0)->after('subtotal_exenta');
            $table->decimal('subtotal_10', 12, 2)->default(0)->after('subtotal_5');
            $table->decimal('tax_5', 12, 2)->default(0)->after('tax');
            $table->decimal('tax_10', 12, 2)->default(0)->after('tax_5');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('tax_percentage', 5, 2)->default(10)->after('price');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_percentage');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('subtotal_exenta', 12, 2)->default(0)->after('subtotal');
            $table->decimal('subtotal_5', 12, 2)->default(0)->after('subtotal_exenta');
            $table->decimal('subtotal_10', 12, 2)->default(0)->after('subtotal_5');
            $table->decimal('tax_5', 12, 2)->default(0)->after('tax');
            $table->decimal('tax_10', 12, 2)->default(0)->after('tax_5');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('tax_percentage', 5, 2)->default(10)->after('price');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['subtotal_exenta', 'subtotal_5', 'subtotal_10', 'tax_5', 'tax_10']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['tax_percentage', 'tax_amount']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['subtotal_exenta', 'subtotal_5', 'subtotal_10', 'tax_5', 'tax_10']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['tax_percentage', 'tax_amount']);
        });
    }
};

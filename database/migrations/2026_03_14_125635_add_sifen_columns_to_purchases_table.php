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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('status');
            $table->string('timbrado', 8)->nullable()->after('invoice_number');
            $table->string('cdc', 44)->nullable()->after('timbrado');
            $table->enum('condition', ['contado', 'credito'])->default('contado')->after('cdc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'timbrado', 'cdc', 'condition']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos SIFEN v150 a la tabla branches.
 *
 * Campos requeridos:
 * - establishment_code (dEst): código de establecimiento SET (3 dígitos, ej: "001")
 * - dispatch_point (dPunExp): punto de expedición SET (3 dígitos, ej: "001")
 * - timbrado_number (dNumTim): número de timbrado otorgado por la SET (8 dígitos)
 * - timbrado_start_date (dFeIniT): fecha de inicio de vigencia del timbrado
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('establishment_code', 3)->default('001')->after('active');
            $table->string('dispatch_point', 3)->default('001')->after('establishment_code');
            $table->string('timbrado_number', 8)->nullable()->after('dispatch_point');
            $table->date('timbrado_start_date')->nullable()->after('timbrado_number');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'establishment_code',
                'dispatch_point',
                'timbrado_number',
                'timbrado_start_date',
            ]);
        });
    }
};

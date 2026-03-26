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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('ruc_dv', 1)->nullable()->after('ruc');
            $table->tinyInteger('tipo_contribuyente')->default(2)->after('ruc_dv'); // 1=Fisica, 2=Juridica
            $table->tinyInteger('tipo_regimen')->default(1)->after('tipo_contribuyente');
            $table->string('num_casa')->default('0')->after('address');
            $table->integer('departamento_code')->nullable()->after('num_casa');
            $table->string('departamento_desc')->nullable()->after('departamento_code');
            $table->integer('ciudad_code')->nullable()->after('departamento_desc');
            $table->string('ciudad_desc')->nullable()->after('ciudad_code');
            $table->string('actividad_eco_code')->nullable()->after('ciudad_desc');
            $table->string('actividad_eco_desc')->nullable()->after('actividad_eco_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'ruc_dv',
                'tipo_contribuyente',
                'tipo_regimen',
                'num_casa',
                'departamento_code',
                'departamento_desc',
                'ciudad_code',
                'ciudad_desc',
                'actividad_eco_code',
                'actividad_eco_desc',
            ]);
        });
    }
};

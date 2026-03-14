<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouse_aisles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_aisle_id')->constrained()->onDelete('cascade');
            $table->string('number');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('shelf_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_id')->constrained()->onDelete('cascade');
            $table->string('number');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('shelf_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_row_id')->constrained()->onDelete('cascade');
            $table->string('number');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('product_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('shelf_level_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_locations');
        Schema::dropIfExists('shelf_levels');
        Schema::dropIfExists('shelf_rows');
        Schema::dropIfExists('shelves');
        Schema::dropIfExists('warehouse_aisles');
        Schema::dropIfExists('warehouses');
    }
};

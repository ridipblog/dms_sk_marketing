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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('product_name');

            $table->string('sku_code', 100)
                ->unique();

            $table->string('hsn_code', 50)
                ->nullable();

            $table->string('size', 50)
                ->nullable();

            $table->string('unit', 20);

            $table->decimal('base_price', 18, 2)
                ->default(0.00);

            $table->enum('status', [
                'active',
                'inactive',
                'blocked'
            ])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

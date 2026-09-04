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
        Schema::create('dealer_companies', function (Blueprint $table) {
            $table->id();

            $table->decimal('total_debit_note_amount', 18, 2)
                ->default(0.00);

            $table->decimal('total_credit_note_amount', 18, 2)
                ->default(0.00);
            $table->foreignId('dealer_id')
                ->constrained('dealers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();


            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->enum('status', [
                'active',
                'inactive',
                'blocked'
            ])->default('active');

            $table->timestamps();

            $table->unique(['dealer_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealer_companies');
    }
};

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
        Schema::create('credit_note_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_track_id')
                ->constrained('payment_tracks')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('cash_discount_slab_id')
                ->nullable()
                ->constrained('cash_discount_slabs')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->integer('nos')->nullable();

            $table->decimal('amount', 18, 2);
            $table->bigInteger('parent_payment_track_id')->nullable();
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_tracks');
    }
};

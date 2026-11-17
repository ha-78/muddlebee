<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One rating (1–5 stars) per person and meal.
     */
    public function up(): void
    {
        Schema::create('meal_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['meal_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_ratings');
    }
};

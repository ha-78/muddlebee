<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A planned meal sits on a stored day. planned_at keeps the order meals were put on a day.
     */
    public function up(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->foreignId('calendar_date_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->timestamp('planned_at')->nullable()->after('calendar_date_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('calendar_date_id');
            $table->dropColumn('planned_at');
        });
    }
};

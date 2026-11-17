<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An archived list is kept, read-only, off the lists page.
     */
    public function up(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('is_public');
            $table->index(['context_id', 'archived_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->dropIndex(['context_id', 'archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};

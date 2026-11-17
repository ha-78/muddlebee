<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Share list items" becomes "Add items to shared lists created by others". Existing grants carry over.
     */
    public function up(): void
    {
        DB::table('user_features')->where('feature', 'publish-list-items')->update(['feature' => 'add-items-to-shared-lists']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('user_features')->where('feature', 'add-items-to-shared-lists')->update(['feature' => 'publish-list-items']);
    }
};

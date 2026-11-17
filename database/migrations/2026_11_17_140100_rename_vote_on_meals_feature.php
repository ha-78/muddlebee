<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Vote on meals" becomes "Rate meals". Existing grants carry over.
     */
    public function up(): void
    {
        DB::table('user_features')->where('feature', 'vote-on-meals')->update(['feature' => 'rate-meals']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('user_features')->where('feature', 'rate-meals')->update(['feature' => 'vote-on-meals']);
    }
};

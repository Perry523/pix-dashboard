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
        Schema::table('pix', function (Blueprint $table) {
            // Composite index for efficient expiration queries
            $table->index(['status', 'expires_at'], 'pix_status_expires_at_index');

            // Index for token lookups
            $table->index('token', 'pix_token_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pix', function (Blueprint $table) {
            $table->dropIndex('pix_status_expires_at_index');
            $table->dropIndex('pix_token_index');
        });
    }
};

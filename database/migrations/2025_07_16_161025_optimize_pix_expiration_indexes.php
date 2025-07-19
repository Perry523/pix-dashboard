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
            // Index for user_id to speed up user-specific queries
            $table->index('user_id', 'pix_user_id_index');

            // Index for created_at for sorting recent PIX
            $table->index('created_at', 'pix_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pix', function (Blueprint $table) {
            $table->dropIndex('pix_user_id_index');
            $table->dropIndex('pix_created_at_index');
        });
    }
};

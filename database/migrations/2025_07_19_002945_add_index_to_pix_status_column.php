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
            $table->index(['user_id', 'status'], 'pix_user_status_index');
            $table->index('status', 'pix_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pix', function (Blueprint $table) {
            $table->dropIndex('pix_user_status_index');
            $table->dropIndex('pix_status_index');
        });
    }
};

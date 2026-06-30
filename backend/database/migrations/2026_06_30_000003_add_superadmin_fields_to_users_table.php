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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('municipality_id')
                ->nullable()
                ->after('id')
                ->constrained('municipalities')
                ->nullOnDelete();

            $table->boolean('is_superadmin')->default(false)->after('password');
            $table->boolean('force_password_change')->default(false)->after('is_superadmin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('municipality_id');
            $table->dropColumn(['is_superadmin', 'force_password_change']);
        });
    }
};

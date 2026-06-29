<?php

declare(strict_types=1);

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
        Schema::table('msc_rules', function (Blueprint $table) {
            $table->boolean('is_implemented')->default(false)->after('error_message');
            $table->index('is_implemented');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('msc_rules', function (Blueprint $table) {
            $table->dropIndex(['is_implemented']);
            $table->dropColumn('is_implemented');
        });
    }
};

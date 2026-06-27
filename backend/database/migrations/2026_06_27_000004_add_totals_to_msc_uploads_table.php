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
        Schema::table('msc_uploads', function (Blueprint $table) {
            $table->unsignedInteger('total_lines')->default(0)->after('tipo_msc');
            $table->unsignedInteger('total_errors')->default(0)->after('total_lines');
            $table->unsignedInteger('total_alerts')->default(0)->after('total_errors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('msc_uploads', function (Blueprint $table) {
            $table->dropColumn(['total_lines', 'total_errors', 'total_alerts']);
        });
    }
};

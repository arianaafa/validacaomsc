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
            $table->string('ibge_code', 7)->nullable()->after('tipo_msc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('msc_uploads', function (Blueprint $table) {
            $table->dropColumn('ibge_code');
        });
    }
};

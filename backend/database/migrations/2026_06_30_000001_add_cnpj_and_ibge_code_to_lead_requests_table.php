<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_requests', function (Blueprint $table) {
            $table->string('cnpj', 14)->after('organization_name');
            $table->string('ibge_code', 7)->after('cnpj');

            $table->index('ibge_code');
        });
    }

    public function down(): void
    {
        Schema::table('lead_requests', function (Blueprint $table) {
            $table->dropIndex(['ibge_code']);
            $table->dropColumn(['cnpj', 'ibge_code']);
        });
    }
};

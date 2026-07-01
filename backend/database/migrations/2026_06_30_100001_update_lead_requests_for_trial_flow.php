<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lead_requests', 'user_id')) {
            Schema::table('lead_requests', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('status')->constrained()->nullOnDelete();
                $table->timestamp('trial_started_at')->nullable()->after('user_id');
                $table->timestamp('trial_expires_at')->nullable()->after('trial_started_at');
                $table->timestamp('approved_at')->nullable()->after('trial_expires_at');
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            $this->migrateLeadStatusColumnForPostgres();

            return;
        }

        DB::table('lead_requests')->where('status', 'pendente')->update(['status' => 'pending']);
        DB::table('lead_requests')->where('status', 'contatado')->update(['status' => 'pending']);
        DB::table('lead_requests')->where('status', 'concluido')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('lead_requests', 'user_id')) {
            Schema::table('lead_requests', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
                $table->dropColumn(['trial_started_at', 'trial_expires_at', 'approved_at']);
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE lead_requests DROP CONSTRAINT IF EXISTS lead_requests_status_check');
            DB::statement('ALTER TABLE lead_requests ALTER COLUMN status DROP DEFAULT');
            DB::statement("ALTER TABLE lead_requests ALTER COLUMN status TYPE VARCHAR(20)");
            DB::statement("UPDATE lead_requests SET status = 'pendente' WHERE status = 'pending'");
            DB::statement("UPDATE lead_requests SET status = 'contatado' WHERE status = 'trial'");
            DB::statement("UPDATE lead_requests SET status = 'concluido' WHERE status = 'approved'");
            DB::statement("UPDATE lead_requests SET status = 'pendente' WHERE status = 'failed'");
            DB::statement("ALTER TABLE lead_requests ALTER COLUMN status SET DEFAULT 'pendente'");
            DB::statement("ALTER TABLE lead_requests ADD CONSTRAINT lead_requests_status_check CHECK (status IN ('pendente', 'contatado', 'concluido'))");

            return;
        }

        DB::table('lead_requests')->where('status', 'pending')->update(['status' => 'pendente']);
        DB::table('lead_requests')->where('status', 'trial')->update(['status' => 'contatado']);
        DB::table('lead_requests')->where('status', 'approved')->update(['status' => 'concluido']);
        DB::table('lead_requests')->where('status', 'failed')->update(['status' => 'pendente']);
    }

    private function migrateLeadStatusColumnForPostgres(): void
    {
        // Laravel cria CHECK (status IN (...)) no PostgreSQL; precisa ser removido antes da conversão.
        DB::statement('ALTER TABLE lead_requests DROP CONSTRAINT IF EXISTS lead_requests_status_check');
        DB::statement('ALTER TABLE lead_requests ALTER COLUMN status DROP DEFAULT');

        DB::statement("ALTER TABLE lead_requests ALTER COLUMN status TYPE VARCHAR(20) USING (
            CASE status::text
                WHEN 'pendente' THEN 'pending'
                WHEN 'contatado' THEN 'pending'
                WHEN 'concluido' THEN 'approved'
                ELSE status::text
            END
        )");

        DB::statement("ALTER TABLE lead_requests ALTER COLUMN status SET DEFAULT 'pending'");

        // Tipo ENUM nativo criado em alguns ambientes (legado).
        DB::statement('DROP TYPE IF EXISTS lead_requests_status_enum');
    }
};

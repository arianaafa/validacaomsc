<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('msc_uploads', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'periodo', 'tipo_msc']);
        });

        $this->removeDuplicateUploads();

        Schema::table('msc_uploads', function (Blueprint $table) {
            $table->unique(['user_id', 'periodo', 'tipo_msc', 'ibge_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('msc_uploads', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'periodo', 'tipo_msc', 'ibge_code']);
        });

        $this->removeDuplicateUploadsByPeriodo();

        Schema::table('msc_uploads', function (Blueprint $table) {
            $table->unique(['user_id', 'periodo', 'tipo_msc']);
        });
    }

    private function removeDuplicateUploads(): void
    {
        $duplicateGroups = DB::table('msc_uploads')
            ->select('user_id', 'periodo', 'tipo_msc', 'ibge_code')
            ->groupBy('user_id', 'periodo', 'tipo_msc', 'ibge_code')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $query = DB::table('msc_uploads')
                ->where('user_id', $group->user_id)
                ->where('periodo', $group->periodo)
                ->where('tipo_msc', $group->tipo_msc);

            if ($group->ibge_code === null) {
                $query->whereNull('ibge_code');
            } else {
                $query->where('ibge_code', $group->ibge_code);
            }

            $uploadIds = $query
                ->orderByDesc('created_at')
                ->pluck('id');

            $uploadIds->slice(1)->each(static function (string $uploadId): void {
                DB::table('msc_uploads')->where('id', $uploadId)->delete();
            });
        }
    }

    private function removeDuplicateUploadsByPeriodo(): void
    {
        $duplicateGroups = DB::table('msc_uploads')
            ->select('user_id', 'periodo', 'tipo_msc')
            ->groupBy('user_id', 'periodo', 'tipo_msc')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $uploadIds = DB::table('msc_uploads')
                ->where('user_id', $group->user_id)
                ->where('periodo', $group->periodo)
                ->where('tipo_msc', $group->tipo_msc)
                ->orderByDesc('created_at')
                ->pluck('id');

            $uploadIds->slice(1)->each(static function (string $uploadId): void {
                DB::table('msc_uploads')->where('id', $uploadId)->delete();
            });
        }
    }
};

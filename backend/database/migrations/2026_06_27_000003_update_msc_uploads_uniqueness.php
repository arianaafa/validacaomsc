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
            $table->dropUnique(['hash']);
        });

        $this->removeDuplicateUploads();

        Schema::table('msc_uploads', function (Blueprint $table) {
            $table->index('hash');
            $table->unique(['user_id', 'periodo', 'tipo_msc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('msc_uploads', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'periodo', 'tipo_msc']);
            $table->dropIndex(['hash']);
            $table->unique('hash');
        });
    }

    private function removeDuplicateUploads(): void
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

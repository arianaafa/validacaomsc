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
        Schema::create('msc_validation_errors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('msc_upload_id')->constrained('msc_uploads')->cascadeOnDelete();
            $table->unsignedInteger('linha')->nullable();
            $table->string('conta_contabil')->nullable();
            $table->enum('tipo', ['erro', 'alerta']);
            $table->string('codigo_regra');
            $table->text('descricao');
            $table->timestamps();

            $table->index('conta_contabil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('msc_validation_errors');
    }
};

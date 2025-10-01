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
        Schema::create('penggajian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('tanggal_gaji');
            $table->string('periode', 7)->comment('Contoh: 2025-09');
            $table->bigInteger('gaji_pokok');
            $table->bigInteger('tunjangan')->default(0);
            $table->bigInteger('potongan')->default(0);

            $table->bigInteger('gaji_bersih');

            $table->enum('status', ['draft', 'paid'])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggajians');
    }
};

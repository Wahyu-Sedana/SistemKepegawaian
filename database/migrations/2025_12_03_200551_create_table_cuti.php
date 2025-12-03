<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuota_cuti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->year('tahun');
            $table->integer('kuota_awal')->default(12);
            $table->integer('kuota_terpakai')->default(0);
            $table->integer('kuota_tersisa')->default(12);
            $table->timestamps();

            $table->unique(['user_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuota_cuti');
    }
};

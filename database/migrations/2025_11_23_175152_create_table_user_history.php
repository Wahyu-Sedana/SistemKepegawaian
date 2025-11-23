<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('user_data');
            $table->timestamp('deleted_at');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('deletion_reason')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('email');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_history');
    }
};

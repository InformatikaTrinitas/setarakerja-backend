<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('Lainnya');
            $table->string('priority', 10)->default('Sedang');
            $table->string('status', 20)->default('dibutuhkan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('needs');
    }
};

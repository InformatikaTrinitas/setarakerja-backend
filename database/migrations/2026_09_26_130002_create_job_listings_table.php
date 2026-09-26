<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('company');
            $table->string('company_size')->nullable();
            $table->string('location');
            $table->string('type', 20)->default('onsite');
            $table->string('salary')->nullable();
            $table->json('skills')->nullable();
            $table->boolean('accessible')->default(true);
            $table->json('accommodations')->nullable();
            $table->json('categories')->nullable();
            $table->string('job_coach')->nullable();
            $table->unsignedSmallInteger('slots')->default(1);
            $table->boolean('verified')->default(false);
            $table->text('description')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};

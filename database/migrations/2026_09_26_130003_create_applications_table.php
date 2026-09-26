<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('job_listing_id')->constrained('job_listings')->cascadeOnDelete();
            $table->string('applicant_name');
            $table->string('applicant_email');
            $table->string('disability')->nullable();
            $table->string('accommodation')->nullable();
            $table->string('status', 30)->default('applied');
            $table->string('anonymous_code', 20)->nullable();
            $table->unsignedTinyInteger('ai_score')->default(0);
            $table->json('skills')->nullable();
            $table->boolean('is_revealed')->default(false);
            $table->string('revealed_name')->nullable();
            $table->string('revealed_disability')->nullable();
            $table->json('revealed_accommodations')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'job_listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Tujuan pesan: lamaran kandidat tertentu atau lowongan tertentu.
            $table->foreignId('application_id')->nullable()->after('user_id')->constrained('applications')->nullOnDelete();
            $table->foreignId('job_id')->nullable()->after('application_id')->constrained('job_listings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('application_id');
            $table->dropConstrainedForeignId('job_id');
        });
    }
};

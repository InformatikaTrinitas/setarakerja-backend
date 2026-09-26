<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            // Titik lokasi kantor/perusahaan yang dipilih HRD (opsional).
            $table->decimal('location_lat', 10, 7)->nullable()->after('location');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['location_lat', 'location_lng']);
        });
    }
};

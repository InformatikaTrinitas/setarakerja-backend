<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('kandidat')->after('password');
            $table->string('title')->nullable()->after('role');
            $table->text('avatar')->nullable()->after('title');
            $table->string('disability_type', 30)->nullable()->after('avatar');
            $table->string('company_name')->nullable()->after('disability_type');
            $table->string('phone', 30)->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'title', 'avatar', 'disability_type', 'company_name', 'phone',
            ]);
        });
    }
};

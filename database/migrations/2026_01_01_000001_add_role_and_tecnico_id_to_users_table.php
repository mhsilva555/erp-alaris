<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('password'); // 'admin', 'tecnico'
            $table->foreignId('tecnico_id')->nullable()->after('role')->constrained('tecnicos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tecnico_id']);
            $table->dropColumn(['role', 'tecnico_id']);
        });
    }
};

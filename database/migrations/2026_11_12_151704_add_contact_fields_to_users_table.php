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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('avatar');
            $table->string('telegram', 64)->nullable()->after('phone');
            $table->string('instagram', 64)->nullable()->after('telegram');
            $table->boolean('show_email')->default(true)->after('instagram');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'telegram', 'instagram', 'show_email']);
        });
    }
};

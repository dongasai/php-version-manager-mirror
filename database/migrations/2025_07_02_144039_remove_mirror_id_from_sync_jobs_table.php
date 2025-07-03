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
        Schema::table('sync_jobs', function (Blueprint $table) {
            // 移除 mirror_id 字段，完全使用硬编码配置
            $table->dropColumn('mirror_id');

            // 确保 mirror_type 字段不为空
            $table->string('mirror_type', 50)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_jobs', function (Blueprint $table) {
            // 恢复 mirror_id 字段
            $table->unsignedBigInteger('mirror_id')->nullable()->after('id');

            // 恢复 mirror_type 为可空
            $table->string('mirror_type', 50)->nullable()->change();
        });
    }
};

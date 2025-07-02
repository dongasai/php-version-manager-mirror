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
            // 添加镜像类型字段
            $table->string('mirror_type', 50)->nullable()->after('mirror_id')->comment('镜像类型');

            // 修改 mirror_id 为可空，因为现在使用硬编码配置
            $table->unsignedBigInteger('mirror_id')->nullable()->change();

            // 添加新的索引
            $table->index(['mirror_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_jobs', function (Blueprint $table) {
            // 删除新添加的字段和索引
            $table->dropIndex(['mirror_type', 'status']);
            $table->dropColumn('mirror_type');

            // 恢复 mirror_id 为非空
            $table->unsignedBigInteger('mirror_id')->nullable(false)->change();
        });
    }
};

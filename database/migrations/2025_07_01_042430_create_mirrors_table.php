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
        Schema::create('mirrors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('镜像名称');
            $table->string('type', 50)->comment('镜像类型');
            $table->string('url', 500)->comment('镜像URL');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用,0禁用');
            $table->json('config')->nullable()->comment('配置信息');
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mirrors');
    }
};

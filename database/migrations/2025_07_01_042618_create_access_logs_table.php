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
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->comment('客户端IP');
            $table->string('path', 500)->comment('请求路径');
            $table->string('method', 10)->comment('请求方法');
            $table->integer('status')->comment('响应状态码');
            $table->bigInteger('size')->default(0)->comment('响应大小');
            $table->text('user_agent')->nullable()->comment('用户代理');
            $table->timestamps();

            $table->index(['ip', 'created_at']);
            $table->index('path');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};

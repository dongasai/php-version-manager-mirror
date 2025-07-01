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
        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mirror_id')->comment('镜像ID');
            $table->string('status', 20)->default('pending')->comment('状态');
            $table->integer('progress')->default(0)->comment('进度百分比');
            $table->text('log')->nullable()->comment('日志信息');
            $table->timestamp('started_at')->nullable()->comment('开始时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();

            $table->foreign('mirror_id')->references('id')->on('mirrors')->onDelete('cascade');
            $table->index(['mirror_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_jobs');
    }
};

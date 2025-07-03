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
        Schema::create('job_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id')->nullable()->comment('关联的队列任务ID');
            $table->string('job_class')->comment('任务类名');
            $table->string('queue', 50)->default('default')->comment('队列名称');
            $table->string('status', 20)->default('running')->comment('执行状态');
            $table->json('payload')->nullable()->comment('任务载荷数据');
            $table->text('output')->nullable()->comment('执行输出');
            $table->text('error')->nullable()->comment('错误信息');
            $table->integer('memory_usage')->nullable()->comment('内存使用量(字节)');
            $table->decimal('execution_time', 8, 3)->nullable()->comment('执行时间(秒)');
            $table->timestamp('started_at')->nullable()->comment('开始时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();

            // 索引
            $table->index(['job_id']);
            $table->index(['job_class', 'status']);
            $table->index(['queue', 'status']);
            $table->index(['status', 'started_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_runs');
    }
};

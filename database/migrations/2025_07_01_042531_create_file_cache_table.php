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
        Schema::create('file_cache', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->comment('文件路径');
            $table->bigInteger('size')->default(0)->comment('文件大小');
            $table->string('hash', 64)->nullable()->comment('文件哈希');
            $table->timestamp('last_modified')->nullable()->comment('最后修改时间');
            $table->timestamps();

            $table->unique('path');
            $table->index('hash');
            $table->index('last_modified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_cache');
    }
};

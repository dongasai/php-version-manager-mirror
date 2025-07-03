<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 每小时执行一次镜像同步检查
        $schedule->command('sync:check')->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // 每天凌晨2点清理过期的同步任务记录
        $schedule->command('sync:clean')->dailyAt('02:00')
            ->withoutOverlapping();

        // 每天凌晨3点清理失败的队列任务
        $schedule->command('queue:prune-failed --hours=168')->dailyAt('03:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

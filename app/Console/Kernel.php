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
        // $schedule->command('inspire')->hourly();
        // $schedule->job(function () {
        //     \Log::info('Running check-advert-price schedule...');
    
        //     $adverts = \App\Models\Advert::with('users')->get();
    
        //     if ($adverts->isEmpty()) {
        //         \Log::error('No adverts found!');
        //         return;
        //     }
    
        //     foreach ($adverts as $advert) {
        //         foreach ($advert->users as $user) {
        //             \Log::info("Dispatching CheckAdvertPrice job for advert ID: {$advert->id}, user ID: {$user->id}");
        //             \App\Jobs\CheckAdvertPrice::dispatch($user, $advert);
        //         }
        //     }
    
        // })->everyMinute()->name('check-advert-price')->withoutOverlapping();
        $schedule->job(new \App\Jobs\CheckAdvertPriceSchedulerJob())->everyMinute()->name('check-advert-price')->withoutOverlapping();

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

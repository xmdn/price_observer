<?php

namespace App\Jobs;

use App\Models\Advert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAdvertPriceSchedulerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Running check-advert-price schedule...');

        $adverts = Advert::with('users')->get();

        if ($adverts->isEmpty()) {
            Log::error('No adverts found!');
            return;
        }

        foreach ($adverts as $advert) {
            foreach ($advert->users as $user) {
                Log::info("Dispatching CheckAdvertPrice job for advert ID: {$advert->id}, user ID: {$user->id}");
                \App\Jobs\CheckAdvertPrice::dispatch($user, $advert);
            }
        }
    }
}

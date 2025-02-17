<?php

namespace App\Jobs;

use App\Models\Advert;
use App\Models\User;
use App\Mail\PriceAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class CheckAdvertPrice implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $advert;
    public $uniqueFor = 120;
    public $tries = 3; // Retry the job up to 3 times
    public $timeout = 60; // Set timeout to 60 seconds

    /**
     * Ensure uniqueness per advert_id
     */
    public function uniqueId()
    {
        return "check_advert_price:{$this->advert->id}";
    }

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, Advert $advert)
    {
        $this->user = $user;
        $this->advert = $advert;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lockKey = "check_advert_price:{$this->advert->id}";

        // Try to acquire a Redis lock (expires in 2 minutes)
        // if (!Redis::setnx($lockKey, now()->timestamp)) {
        //     \Log::info("Job already running for advert ID {$this->advert->id}. Skipping.");
        //     return;
        // }

        Redis::expire($lockKey, 120); // Set expiration for 2 minutes (prevents stale locks)


        try {
            $advert = $this->advert;
            $user = $this->user;

            $cachedPrice = Cache::get($cacheKey);
            if ($cachedPrice) {
                Log::info("Retrieved price from cache for advert ID {$advert->advert_id}: {$cachedPrice}");

                // If the cached price is the same, avoid processing
                if ($cachedPrice == $advert->price) {
                    Log::info("Price unchanged for advert ID {$advert->advert_id}, skipping update.");
                    return;
                }
            }
            
            $url = "https://m.olx.ua/api/v2/offers/{$advert->advert_id}/";

            $response = Http::get($url);
            if ($response->failed()) {
                \Log::error("Failed to fetch OLX advert data for {$advert->advert_id}");
                return;
            }

            $data = $response->json();

            \Log::info("OLX API Response:", $data);

            // 🔴 Log the FULL API response to debug what's missing
            \Log::info("OLX API Response for advert ID {$advert->advert_id}: " . json_encode($data));

            // Mail::to($user->email)->send(new PriceAlert($advert, $advert->price));

            // 🔍 Find price in `params` array
            $newPrice = null;
            foreach ($data['data']['params'] as $param) {
                if ($param['key'] === 'price' && isset($param['value']['value'])) {
                    $newPrice = $param['value']['value'];
                    break;
                }
            }

            if (!$newPrice) {
                \Log::warning("Price not found in response for {$advert->advert_id}");
                return;
            }

            Cache::put($cacheKey, $newPrice, now()->addMinutes(10));
            
            // Check if price has changed
            if ($newPrice !== $advert->price) {
                // Update advert price
                $advert->update(['price' => $newPrice]);

                // Send notification email
                Mail::to($user->email)->send(new PriceAlert($advert, $newPrice));

                \Log::info("Price updated for {$advert->advert_id} - New Price: {$newPrice}");
            }

            // Release Redis lock
            Redis::del($lockKey);

            // Re-dispatch the job with a 10-minute delay
            self::dispatch($user, $advert)->delay(now()->addSeconds(7));
        } catch (\Exception $e) {
            \Log::error("Error processing advert ID {$this->advert->id}: " . $e->getMessage());
        } finally {
            // Ensure Redis lock is released even if an error occurs
            Redis::del($lockKey);
        }
    }
}

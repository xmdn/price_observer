<?php

namespace App\Http\Controllers;

use App\Models\Advert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\CheckAdvertPrice;

class AdvertController extends Controller
{
    public function index()
    {
        $adverts = Cache::rememberForever('adverts:all', function () {
            return Advert::all();
        });
        dd($adverts->pluck('title'));
    }
    public function scrape(Request $request)
    {
        $request->validate([
            'url'   => 'required|url',
            'email' => 'required|email',
        ]);

        $url = $request->input('url');
        $email = $request->input('email');

        // Find or create user
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Default Name'] // Set a default name
        );

        // Run the scraping logic
        // $command = "sail artisan scrape:olx_advert \"$url\"";
        $response = Artisan::call('scrape:olx_advert', [
            'url' => $url,
        ]);

        $output = Artisan::output();

        // Extract last line from the output
        $lines = explode("\n", trim($output));
        $jsonString = end($lines);

        $advertData = json_decode($jsonString, true);
        
        // Ensure JSON decoding was successful
        if (!$advertData || !isset($advertData['id'])) {
            return response()->json(['error' => 'Failed to parse advert data'], 500);
        }

        // Fetch the Advert from the database using the ID
        $advert = Advert::find($advertData['id']);

        if (!$advert) {
            return response()->json(['error' => 'Advert not found in the database'], 500);
        }

        // Subscribe user to advert
        $user->adverts()->syncWithoutDetaching([$advert->id]);

        // Dispatch Queue Job
        CheckAdvertPrice::dispatch($user, $advert);

        return response()->json([
            'message' => "Subscribed for price alerts on advert {$advert->id}.",
            'advert'  => $advert,
        ], 200);

        

        return response()->json(['message' => "Subscribed for price alerts on advert {$advert->advert_id}."], 200);
    }

    public function checkPriceChanges()
    {
        $adverts = Advert::all();
        foreach ($adverts as $advert) {
            $response = Http::get("https://m.olx.ua/api/v2/offers/{$advert->advert_id}/");
            if ($response->successful()) {
                $data = $response->json();
                $newPrice = $data['data']['params']['price']['value'] ?? 0;

                if ($advert->price != $newPrice) {
                    $advert->update(['price' => $newPrice]);
                    \Mail::to('example@gmail.com')->send(new \App\Mail\PriceAlert($advert));
                }
            }
        }
    }
}

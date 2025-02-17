<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdvertController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Models\User;

use Illuminate\Http\Request;

use Laravel\Socialite\Facades\Socialite;

use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/auth/redirect', function () {
    // Tell Socialite to begin the OAuth flow with OLX.
    return Socialite::driver('olx')
        ->scopes(['v2','read','write'])
        ->redirect();
});

// Route::get('/auth/callback', function () {
//     try {
//         // Fetch user data from OLX
//         $user = Socialite::driver('olx')->user();
//         // Debug: Show retrieved user data
//         dd($user);
//     } catch (Exception $e) {
//         return redirect('/')->with('error', 'Authentication failed.');
//     }
// });
Route::get('/auth/callback', function (Request $request) {
    if (!$request->has('code')) {
        return dd('Authorization failed: Missing code.');
        return redirect('/')->with('error', 'Authorization failed: Missing code.');
    }
    return dd($request->query('code'));

    $response = Http::asForm()->post('https://www.olx.ua/api/open/oauth/token', [
        'grant_type'    => 'authorization_code',
        'client_id'     => env('OLX_CLIENT_ID'),
        'client_secret' => env('OLX_CLIENT_SECRET'),
        'code'          => $request->query('code'),
        'redirect_uri'  => env('OLX_REDIRECT_URI'),
        'scope'         => 'v2 read write',
    ]);

    if ($response->failed()) {
        return redirect('/')->with('error', 'Authentication failed.');
    }

    $tokenData = $response->json();
    return dd($tokenData);

    return redirect('/dashboard')->with('token', $tokenData);
});

Route::get('/adverts', [AdvertController::class, 'index']);

Route::post('/scrape', [AdvertController::class, 'scrape']);
Route::delete('/scrape/stop/{advert_id}', function (Request $request, $advert_id) {
    $user = User::where('email', $request->input('email'))->first();

    if (!$user) {
        return response()->json(['error' => 'User not found.'], 404);
    }

    $advert = Advert::where('advert_id', $advert_id)->first();

    if (!$advert) {
        return response()->json(['error' => 'Advert not found.'], 404);
    }

    // Unsubscribe user from advert
    $user->adverts()->detach($advert->id);

    return response()->json(['message' => "Stopped monitoring advert {$advert_id} for user {$user->email}."], 200);
});

Route::get('/test-email', function () {
    $recipient = 'wpredhops@gmail.com'; // Change this to your email

    Mail::raw('This is a test email from your Laravel application.', function ($message) use ($recipient) {
        $message->to($recipient)->subject('Laravel SendGrid Test Email');
    });

    return response()->json(['message' => 'Test email sent to ' . $recipient]);
});

Route::get('/check-price-changes', [AdvertController::class, 'checkPriceChanges']);

require __DIR__.'/auth.php';

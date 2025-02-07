<?php

namespace App\Http\Controllers;

use App\Models\Advert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdvertController extends Controller
{
    public function index()
    {
        $adverts = Cache::rememberForever('adverts:all', function () {
            return Advert::all();
        });
        dd($adverts->pluck('title'));
    }
}

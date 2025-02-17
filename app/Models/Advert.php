<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Advert extends Model
{
    use HasFactory;
    protected $guarded = false;
    public $incrementing = true;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_advert', 'advert_id', 'user_id')
                    ->withTimestamps();
    }
}

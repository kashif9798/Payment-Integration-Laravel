<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Subscription;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'price',
        'duration_in_days'
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function getVisualPriceAttribute()
    {
        return '$' . number_format($this->price / 100, 2, '.', ',');
    }
}

<?php

namespace App\Models;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'active_until',
        'user_id',
        'plan_id'
    ];

    protected $casts = [
        'active_until' => 'date'
    ];

    public function user()
    {
        return $this->belongsto(User::class);
    }

    public function plan()
    {
        return $this->belongsto(Plan::class);
    }

    public function isActive()
    {
        // rememeber above we casted active_until as date type so carbon is automatically applied to it
        // gt is alias for greateThan() in carbaon which means if one time greater than the other so if the active_until is greater than the now time than the subscription is still active otherwise not
        //this function can be used in the user model to know that if a user has an active subscription
        return $this->active_until->gt(now());
    }
}

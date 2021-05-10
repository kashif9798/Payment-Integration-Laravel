<?php

namespace App\Models;

use App\Models\Subscription;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * to know if this user has active subscritpion
     */
    public function hasActiveSubscription()
    {
        // not all users will have subscriptions so for it we will use optional helper from laravel and null coalescing operator
        //The optional function accepts any argument and allows you to access properties or call methods on that object. If the given object is null, properties and methods will return null instead of causing an error.
        // we still have a problem as if $this->subscription returns null than isActive will be called on null and produce an error so we will use null coalescing operator
        // null coalescing operator: works as a short hand version of isset i.e:
        // isset(optional($this->subscription)->isActive()) ? optional($this->subscription)->isActive() : false

        // return true;
        return optional($this->subscription)->isActive() ?? false;
    }
}

<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function createdKomunitas()
    {
        return $this->hasMany(Komunitas::class, 'creator_id');
    }

    public function komunitasMemberships()
    {
        return $this->belongsToMany(Komunitas::class, 'komunitas_members')
            ->withPivot('status')
            ->withTimestamps();
    }
}

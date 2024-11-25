<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    protected $fillable = [
        'name',
        'type',
        'location',
        'facilities',
        'price_per_hour',
        'available'
    ];

    protected $casts = [
        'facilities' => 'array',
        'available' => 'boolean',
        'price_per_hour' => 'decimal:2'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}

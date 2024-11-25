<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Court extends Model
{
    use HasFactory;
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

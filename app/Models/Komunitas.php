<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Komunitas extends Model
{
    protected $fillable = [
        'nama_komunitas',
        'deskripsi_komunitas',
        'jumlah_anggota',
        'creator_id'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'komunitas_members')
            ->withPivot('status')
            ->withTimestamps();
    }
}

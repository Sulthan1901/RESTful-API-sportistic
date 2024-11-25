<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Komunitas extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama_komunitas',
        'deskripsi_komunitas',
        'jumlah_anggota',
        'batas_anggota',
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

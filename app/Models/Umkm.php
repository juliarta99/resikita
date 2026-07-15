<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Umkm extends Model
{
    protected $table = 'umkm';

    protected $fillable = ['nama', 'status', 'deskripsi', 'alamat', 'lat', 'lng', 'no_hp', 'foto', 'banjar_id'];

    public function banjarDinas(): BelongsTo
    {
        return $this->belongsTo(BanjarDinas::class, 'banjar_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
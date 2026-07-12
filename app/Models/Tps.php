<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tps extends Model
{
    protected $table = 'tps';

    protected $fillable = ['nama', 'alamat', 'lat', 'lng', 'no_hp', 'foto', 'is_berbayar', 'tarif', 'banjar_id'];

    protected function casts(): array
    {
        return [
            'is_berbayar' => 'boolean',
        ];
    }

    public function banjarDinas(): BelongsTo
    {
        return $this->belongsTo(BanjarDinas::class, 'banjar_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TpsMember::class);
    }
}

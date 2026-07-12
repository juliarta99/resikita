<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCategory extends Model
{
    protected $fillable = ['nama', 'deskripsi'];

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'kategori_id');
    }
}

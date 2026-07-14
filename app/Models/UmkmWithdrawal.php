<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmWithdrawal extends Model
{
    protected $fillable = ['umkm_id', 'jumlah', 'nama_bank', 'no_rekening', 'atas_nama', 'status', 'catatan', 'approved_by'];
    protected $casts = ['jumlah' => 'decimal:2'];

    public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
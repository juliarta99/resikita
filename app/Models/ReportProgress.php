<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportProgress extends Model
{
    protected $table = 'report_progress';

    protected $fillable = ['report_id', 'petugas_id', 'catatan', 'foto_bukti', 'status_progress', 'lat', 'lng'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}

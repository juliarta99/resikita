<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    protected $fillable = ['scope_type', 'scope_id', 'periode', 'konten', 'raw_response', 'generated_by'];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
        ];
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

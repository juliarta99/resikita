<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PeranChat;
use App\Enums\SumberInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatPesan extends Model
{
    use HasFactory;

    protected $table = 'chat_pesan';

    protected $fillable = [
        'sesi_id',
        'role',
        'konten',
        'sumber_input',
        'dibacakan',
        'model_version',
    ];

    protected function casts(): array
    {
        return [
            'role' => PeranChat::class,
            'sumber_input' => SumberInput::class,
            'dibacakan' => 'boolean',
        ];
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(ChatSesi::class, 'sesi_id');
    }
}

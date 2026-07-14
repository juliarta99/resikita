<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['user_id', 'order_id', 'product_id', 'umkm_id', 'rating', 'komentar'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderConsumable extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'reagent_id', 'cantidad'];

    protected $casts = ['cantidad' => 'decimal:2'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function reagent(): BelongsTo { return $this->belongsTo(Reagent::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = ['reagent_id', 'tipo_movimiento', 'cantidad', 'motivo', 'order_id', 'user_id', 'fecha_movimiento'];

    protected $casts = ['cantidad' => 'decimal:2', 'fecha_movimiento' => 'datetime'];

    public function reagent(): BelongsTo { return $this->belongsTo(Reagent::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

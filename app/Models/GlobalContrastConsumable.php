<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalContrastConsumable extends Model
{
    use HasFactory;

    protected $fillable = ['tipo_contraste', 'reagent_id', 'cantidad_estimada'];

    protected $casts = ['cantidad_estimada' => 'decimal:2'];

    public function reagent(): BelongsTo
    {
        return $this->belongsTo(Reagent::class);
    }
}

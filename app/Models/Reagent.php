<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reagent extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'stock_actual', 'unidad', 'stock_minimo', 'activo'];

    protected $casts = ['stock_actual' => 'decimal:2', 'stock_minimo' => 'decimal:2', 'activo' => 'boolean'];

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_reagent')
            ->withPivot('cantidad_estimada')
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}

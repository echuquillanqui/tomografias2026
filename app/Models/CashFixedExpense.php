<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashFixedExpense extends Model
{
    use HasFactory;

    protected $fillable = ['descripcion', 'monto', 'activo', 'created_by'];

    protected $casts = ['monto' => 'decimal:2', 'activo' => 'boolean'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CashExpense::class);
    }
}

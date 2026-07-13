<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashExpense extends Model
{
    use HasFactory;

    protected $fillable = ['fecha_egreso', 'descripcion', 'monto', 'archivo_path', 'created_by'];

    protected $casts = ['fecha_egreso' => 'date', 'monto' => 'decimal:2'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

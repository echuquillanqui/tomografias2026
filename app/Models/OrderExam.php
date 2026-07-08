<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderExam extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'exam_id', 'tipo_contraste', 'precio', 'estado', 'comision_porcentaje', 'comision_monto'];

    protected $casts = ['precio' => 'decimal:2', 'comision_porcentaje' => 'decimal:2', 'comision_monto' => 'decimal:2'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function exam(): BelongsTo { return $this->belongsTo(Exam::class); }
}

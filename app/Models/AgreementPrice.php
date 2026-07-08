<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementPrice extends Model
{
    use HasFactory;

    protected $fillable = ['agreement_id', 'exam_id', 'tipo_contraste', 'precio_pactado'];

    protected $casts = ['precio_pactado' => 'decimal:2'];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}

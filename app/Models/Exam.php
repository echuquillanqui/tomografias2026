<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = ['nombre_examen', 'tipo_contraste', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function prices(): HasMany
    {
        return $this->hasMany(AgreementPrice::class);
    }

    public function reagents(): BelongsToMany
    {
        return $this->belongsToMany(Reagent::class, 'exam_reagent')
            ->withPivot('cantidad_estimada')
            ->withTimestamps();
    }

    public function orderExams(): HasMany
    {
        return $this->hasMany(OrderExam::class);
    }
}

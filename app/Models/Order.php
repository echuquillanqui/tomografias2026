<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['codigo_orden', 'unidad', 'patient_id', 'agreement_id', 'medico_solicitante_id', 'medico_informe_id', 'archivo_orden_path', 'fecha_orden', 'estado', 'tipo_pago', 'tipo_comprobante', 'numero_comprobante', 'subtotal', 'descuento', 'total', 'observaciones', 'created_by'];

    protected $casts = ['fecha_orden' => 'datetime', 'subtotal' => 'decimal:2', 'descuento' => 'decimal:2', 'total' => 'decimal:2'];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function agreement(): BelongsTo { return $this->belongsTo(Agreement::class); }
    public function medicoSolicitante(): BelongsTo { return $this->belongsTo(RequestingDoctor::class, 'medico_solicitante_id'); }
    public function medicoInforme(): BelongsTo { return $this->belongsTo(User::class, 'medico_informe_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function orderExams(): HasMany { return $this->hasMany(OrderExam::class); }
    public function report(): HasOne { return $this->hasOne(OrderReport::class); }
    public function admissionForm(): HasOne { return $this->hasOne(OrderAdmissionForm::class); }
    public function swornDeclaration(): HasOne { return $this->hasOne(OrderSwornDeclaration::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }
    public function consumables(): HasMany { return $this->hasMany(OrderConsumable::class); }
}

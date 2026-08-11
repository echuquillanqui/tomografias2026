<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReportAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_report_id', 'original_name', 'stored_name', 'mime_type',
        'original_size', 'stored_size', 'compressed',
    ];

    protected $casts = ['compressed' => 'boolean'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(OrderReport::class, 'order_report_id');
    }
}

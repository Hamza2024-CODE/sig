<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationdecisionOperation extends Model
{
    protected $table      = 'operationdecision_operations';
    protected $primaryKey = 'IDOperationDecition_Operations';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDOperations',
        'IDOperationDecition',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function operation()
    {
        return $this->belongsTo(\Operation::class, 'IDOperations', 'IDOperations');
    }

    public function operationDecition()
    {
        return $this->belongsTo(\OperationDecition::class, 'IDOperationDecition', 'IDOperationDecition');
    }
}
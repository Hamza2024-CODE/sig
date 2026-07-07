<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationnatureOperation extends Model
{
    protected $table      = 'operationnature_operations';
    protected $primaryKey = 'IDOperationNature_Operations';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDOperations',
        'IDOperationNature',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function operation()
    {
        return $this->belongsTo(\Operation::class, 'IDOperations', 'IDOperations');
    }

    public function operationNature()
    {
        return $this->belongsTo(\OperationNature::class, 'IDOperationNature', 'IDOperationNature');
    }
}
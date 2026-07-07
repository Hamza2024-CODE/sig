<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Situation extends Model
{
    protected $table      = 'situation';
    protected $primaryKey = 'IDSituation';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Num',
        'Date',
        'IDOperationLot',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function operationLot()
    {
        return $this->belongsTo(\OperationLot::class, 'IDOperationLot', 'IDOperationLot');
    }
}
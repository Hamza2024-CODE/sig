<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operationoffre extends Model
{
    protected $table      = 'operationoffre';
    protected $primaryKey = 'IDOperationOffre';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Montant',
        'Montantavant',
        'MontantApret',
        'IDOffert',
        'IDOperationLot',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function offert()
    {
        return $this->belongsTo(\Offert::class, 'IDOffert', 'IDOffert');
    }

    public function operationLot()
    {
        return $this->belongsTo(\OperationLot::class, 'IDOperationLot', 'IDOperationLot');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avenat extends Model
{
    protected $table      = 'avenat';
    protected $primaryKey = 'IDAvenat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Montant',
        'DureeAdd',
        'IDOperationLot',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function operationLot()
    {
        return $this->belongsTo(\OperationLot::class, 'IDOperationLot', 'IDOperationLot');
    }
}
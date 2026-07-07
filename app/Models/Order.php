<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table      = 'order';
    protected $primaryKey = 'IDOrder';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Num',
        'Date',
        'Cause',
        'Obs',
        'IDOperationLot',
        'IDOrderNature',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function operationLot()
    {
        return $this->belongsTo(\OperationLot::class, 'IDOperationLot', 'IDOperationLot');
    }

    public function orderNature()
    {
        return $this->belongsTo(\OrderNature::class, 'IDOrderNature', 'IDOrderNature');
    }
}
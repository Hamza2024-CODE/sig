<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CycleScol extends Model
{
    protected $table      = 'cycle_scol';
    protected $primaryKey = 'IDCycle_scol';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NumOrd',
        'Nom',
        'NomFr',
    ];
}
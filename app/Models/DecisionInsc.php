<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionInsc extends Model
{
    protected $table      = 'decision_insc';
    protected $primaryKey = 'IDDecision_Insc';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Ord',
    ];
}
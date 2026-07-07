<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionEvalf extends Model
{
    protected $table      = 'decision_evalf';
    protected $primaryKey = 'IDDecision_evalf';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Ord',
    ];
}
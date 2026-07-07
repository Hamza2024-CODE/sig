<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionEval extends Model
{
    protected $table      = 'decision_evals';
    protected $primaryKey = 'IDDecision_evals';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}
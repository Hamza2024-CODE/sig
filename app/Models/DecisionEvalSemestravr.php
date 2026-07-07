<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionEvalSemestravr extends Model
{
    protected $table      = 'decision_eval_semestravr';
    protected $primaryKey = 'IDDecision_evalsAvr';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}
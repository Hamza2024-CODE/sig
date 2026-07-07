<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionEvalMdl extends Model
{
    protected $table      = 'decision_eval_mdl';
    protected $primaryKey = 'IDDecision_Eval_Mdl';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}
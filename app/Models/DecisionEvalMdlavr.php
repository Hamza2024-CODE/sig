<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionEvalMdlavr extends Model
{
    protected $table      = 'decision_eval_mdlavr';
    protected $primaryKey = 'IDDecision_Eval_MdlAvr';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}
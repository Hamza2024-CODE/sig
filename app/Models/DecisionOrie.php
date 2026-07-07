<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionOrie extends Model
{
    protected $table      = 'decision_orie';
    protected $primaryKey = 'IDDecision_orie';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Ord',
    ];
}
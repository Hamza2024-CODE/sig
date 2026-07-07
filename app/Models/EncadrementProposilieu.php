<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementProposilieu extends Model
{
    protected $table      = 'encadrement_proposilieu';
    protected $primaryKey = 'IDEncadrement_ProposiLieu';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NomOrd',
    ];
}
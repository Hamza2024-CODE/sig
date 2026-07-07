<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdequipementEtattype extends Model
{
    protected $table      = 'idequipement_etattype';
    protected $primaryKey = 'IDIDequipement_etatType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
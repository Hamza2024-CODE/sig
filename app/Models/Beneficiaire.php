<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiaire extends Model
{
    protected $table      = 'beneficiaire';
    protected $primaryKey = 'IDBeneficiaire';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Ncompte',
        'RefPiece',
        'DatePiece',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Annee extends Model
{
    protected $table      = 'annee';
    protected $primaryKey = 'IDannee';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Dated',
        'Datef',
        'Code',
        'Cloturee',
        'Fermee',
        'Encour',
    ];
}
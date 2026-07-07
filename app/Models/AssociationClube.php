<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssociationClube extends Model
{
    protected $table      = 'association_clube';
    protected $primaryKey = 'IDAssociation_Clube';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
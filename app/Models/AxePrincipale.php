<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AxePrincipale extends Model
{
    protected $table      = 'axe_principale';
    protected $primaryKey = 'IDaxe_principale';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}
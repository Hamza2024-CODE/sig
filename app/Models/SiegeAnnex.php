<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiegeAnnex extends Model
{
    protected $table      = 'siege_annex';
    protected $primaryKey = 'IDsiege_annex';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
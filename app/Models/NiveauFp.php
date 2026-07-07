<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NiveauFp extends Model
{
    protected $table      = 'niveau_fp';
    protected $primaryKey = 'IDNiveau_Fp';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumNiveau_Fp',
    ];
}
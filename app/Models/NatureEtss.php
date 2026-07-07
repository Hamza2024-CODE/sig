<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NatureEtss extends Model
{
    protected $table      = 'nature_etss';
    protected $primaryKey = 'IDNature_EtsS';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Abr',
        'AbrFr',
    ];
}
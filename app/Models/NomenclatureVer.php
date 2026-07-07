<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomenclatureVer extends Model
{
    protected $table      = 'nomenclature_ver';
    protected $primaryKey = 'IDNomenclature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Anne',
        'Mois',
        'Obs',
        'activee',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Identite extends Model
{
    protected $table      = 'identites';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'nin',
        'nom',
        'prenom',
        'date_naissance',
        'presume',
        'Date_presume',
        'commune_naissance',
        'pays_naissance',
        'sexe',
        'numero_acte_naissance',
        'nom_ar',
        'prenom_ar',
        'date_deces',
        'create_time',
        'update_time',
        'data_sync_time',
    ];
}
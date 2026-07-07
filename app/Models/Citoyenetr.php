<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citoyenetr extends Model
{
    protected $table      = 'citoyenetr';
    protected $primaryKey = 'IDcitoyenEtr';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Prenom',
        'PrenomFr',
        'DateNais',
        'LieuNais',
        'Civ',
        'datearrivealger',
        'datearriveets',
        'dateretouralger',
        'datedepartalgerie',
        'Tel',
        'Tel2',
        'adresorigin',
        'adresLocal',
        'LieuNaisFr',
    ];
}
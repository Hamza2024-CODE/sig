<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeGestion extends Model
{
    protected $table      = 'mode_gestion';
    protected $primaryKey = 'IDMode_gestion';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Abr',
        'AbrFr',
        'Code',
        'numOrd',
        'Description',
    ];
}
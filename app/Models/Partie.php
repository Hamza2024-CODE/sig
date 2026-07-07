<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partie extends Model
{
    protected $table      = 'partie';
    protected $primaryKey = 'IDPartie';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'CODE_PRTIE',
        'INT_PRTIE',
        'INT_FR_PRTIE',
        'CODE_ARTCL',
        'NUMORD_P',
        'POURCENTAGE',
    ];
}
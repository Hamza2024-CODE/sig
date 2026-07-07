<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeRecrutement extends Model
{
    protected $table      = 'mode_recrutement';
    protected $primaryKey = 'IDMode_Recrutement';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
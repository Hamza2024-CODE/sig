<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeEndroit extends Model
{
    protected $table      = 'mode_endroit';
    protected $primaryKey = 'IDMode_endroit';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
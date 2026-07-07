<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleModeenseigne extends Model
{
    protected $table      = 'module_modeenseigne';
    protected $primaryKey = 'IDModule_ModeEnseigne';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
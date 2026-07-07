<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomenclatureMode extends Model
{
    protected $table      = 'nomenclature_mode';
    protected $primaryKey = 'IDNomenclature_Mode';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Code',
    ];
}
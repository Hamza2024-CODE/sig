<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Corps extends Model
{
    protected $table      = 'corps';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'CodeCorp',
        'NomFrCorp',
        'NomCorp',
        'CodeSct',
        'DecretCorp',
        'DecretFrCorp',
        'CodeNomCorps',
    ];
}
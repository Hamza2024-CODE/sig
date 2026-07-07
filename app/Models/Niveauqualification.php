<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Niveauqualification extends Model
{
    protected $table      = 'niveauqualifications';
    protected $primaryKey = 'IDniveauqualifications';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'id',
        'code',
        'libelleArabe',
        'libelleLatin',
    ];
}
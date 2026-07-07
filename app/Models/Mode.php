<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mode extends Model
{
    protected $table      = 'mode';
    protected $primaryKey = 'IDmode';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'id',
        'code',
        'libelleArabe',
        'libelleLatin',
        'maxage',
        'minage',
    ];
}
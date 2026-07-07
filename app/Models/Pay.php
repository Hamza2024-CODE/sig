<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pay extends Model
{
    protected $table      = 'pays';
    protected $primaryKey = 'IDpays';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'id',
        'code',
        'libelleArabe',
        'libelleLatin',
        'clibelleArabe',
        'clibelleLatin',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nationalite extends Model
{
    protected $table      = 'nationalites';
    protected $primaryKey = 'IDnationalites';
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
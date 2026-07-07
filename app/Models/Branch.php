<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table      = 'branches';
    protected $primaryKey = 'IDbranches';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'id',
        'code',
        'libelleArabe',
        'libelleLatin',
        'isActived',
    ];
}
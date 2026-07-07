<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operationnature extends Model
{
    protected $table      = 'operationnature';
    protected $primaryKey = 'IDOperationNature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
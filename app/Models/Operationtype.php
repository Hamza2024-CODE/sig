<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operationtype extends Model
{
    protected $table      = 'operationtype';
    protected $primaryKey = 'IDOperationType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
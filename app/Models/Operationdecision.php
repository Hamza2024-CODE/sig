<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operationdecision extends Model
{
    protected $table      = 'operationdecision';
    protected $primaryKey = 'IDOperationDecition';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
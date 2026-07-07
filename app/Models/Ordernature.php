<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ordernature extends Model
{
    protected $table      = 'ordernature';
    protected $primaryKey = 'IDOrderNature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
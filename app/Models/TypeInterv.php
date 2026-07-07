<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeInterv extends Model
{
    protected $table      = 'type_interv';
    protected $primaryKey = 'IDType_Interv';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
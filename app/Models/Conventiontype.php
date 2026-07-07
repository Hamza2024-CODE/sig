<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conventiontype extends Model
{
    protected $table      = 'conventiontype';
    protected $primaryKey = 'IDconventionType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}
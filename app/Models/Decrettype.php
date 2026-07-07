<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Decrettype extends Model
{
    protected $table      = 'decrettype';
    protected $primaryKey = 'IDDecretType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}
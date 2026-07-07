<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conventionetat extends Model
{
    protected $table      = 'conventionetat';
    protected $primaryKey = 'IDConventionEtat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
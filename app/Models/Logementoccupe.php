<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logementoccupe extends Model
{
    protected $table      = 'logementoccupe';
    protected $primaryKey = 'Occupe';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
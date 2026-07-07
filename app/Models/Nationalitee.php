<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nationalitee extends Model
{
    protected $table      = 'nationalitee';
    protected $primaryKey = 'IDNationalitee';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
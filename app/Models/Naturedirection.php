<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Naturedirection extends Model
{
    protected $table      = 'naturedirection';
    protected $primaryKey = 'IDNature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Classement',
    ];
}
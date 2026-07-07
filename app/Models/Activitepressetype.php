<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activitepressetype extends Model
{
    protected $table      = 'activitepressetype';
    protected $primaryKey = 'IDActivitePresseType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
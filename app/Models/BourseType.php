<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BourseType extends Model
{
    protected $table      = 'bourse_type';
    protected $primaryKey = 'IDBourse_type';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
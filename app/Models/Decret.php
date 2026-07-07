<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Decret extends Model
{
    protected $table      = 'decret';
    protected $primaryKey = 'IDDecret';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'contenu',
        'contenufr',
        'DateDecret',
    ];
}
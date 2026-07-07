<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fonctiontype extends Model
{
    protected $table      = 'fonctiontype';
    protected $primaryKey = 'IDfonctiontype';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'Nom_Fr',
    ];
}
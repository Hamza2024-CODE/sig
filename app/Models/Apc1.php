<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apc1 extends Model
{
    protected $table      = 'apc1';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'CODE_GEO',
        'LIB_AR',
        'LIB_FR',
        'Wilaya_AR',
        'Wilaya_FR',
        'COD_wilaya',
        'APC_AR',
        'APC_FR',
    ];
}
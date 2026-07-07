<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tbudget extends Model
{
    protected $table      = 'tbudget';
    protected $primaryKey = 'IDTbudget';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
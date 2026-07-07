<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cartecarburant extends Model
{
    protected $table      = 'cartecarburant';
    protected $primaryKey = 'IDCartecarburant';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NumserieCarte',
        'DateCreation',
        'Dateperemption',
        'CodeSecret',
        'montantMax',
        'NumOrd',
    ];
}
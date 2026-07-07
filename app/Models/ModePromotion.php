<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModePromotion extends Model
{
    protected $table      = 'mode_promotion';
    protected $primaryKey = 'IDMode_Promotion';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
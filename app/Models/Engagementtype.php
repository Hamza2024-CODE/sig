<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Engagementtype extends Model
{
    protected $table      = 'engagementtype';
    protected $primaryKey = 'IDEngagementtype';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
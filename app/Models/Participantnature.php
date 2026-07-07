<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participantnature extends Model
{
    protected $table      = 'participantnature';
    protected $primaryKey = 'IDParticipantNature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}
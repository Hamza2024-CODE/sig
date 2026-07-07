<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $table      = 'participant';
    protected $primaryKey = 'IDParticipant';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Prenom',
        'PrenomFr',
        'IDParticipantNature',
        'IDAtelier',
        'NumTel',
        'Email',
        'DateArriv´┐¢',
        'DateDepart',
        'IDActivit´┐¢',
        'IDDFEP',
        'Obs',
        'pr´┐¢sence',
        'FonctionGrade',
        'badge',
        'Civ',
        'IDEncadrement',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function participantNature()
    {
        return $this->belongsTo(\ParticipantNature::class, 'IDParticipantNature', 'IDParticipantNature');
    }

    public function atelier()
    {
        return $this->belongsTo(\Atelier::class, 'IDAtelier', 'IDAtelier');
    }

    public function activit´┐¢()
    {
        return $this->belongsTo(\Activit´┐¢::class, 'IDActivit´┐¢', 'IDActivit´┐¢');
    }

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}
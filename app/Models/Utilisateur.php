<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Utilisateur extends Model
{
    protected $table      = 'utilisateur';
    protected $primaryKey = 'IDUtilisateur';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Code',
        'NomUser',
        'MotPass',
        'Nom',
        'DroiAjout',
        'DroiModif',
        'DroitSuppr',
        'BOUTON',
        'DroitTous',
        'PC_connect',
        'IDBureau',
        'Plan',
        'admin',
        'activee',
        'IDNature',
        'IDMode_formation',
        'IDdirection',
        'IDMode_gestion',
        'IDNature1',
        'admins',
        'avatar',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function bureau()
    {
        return $this->belongsTo(\Bureau::class, 'IDBureau', 'IDBureau');
    }

    public function nature()
    {
        return $this->belongsTo(\Nature::class, 'IDNature', 'IDNature');
    }

    public function modeFormation()
    {
        return $this->belongsTo(\ModeFormation::class, 'IDMode_formation', 'IDMode_formation');
    }

    public function direction()
    {
        return $this->belongsTo(\Direction::class, 'IDdirection', 'IDdirection');
    }

    public function modeGestion()
    {
        return $this->belongsTo(\ModeGestion::class, 'IDMode_gestion', 'IDMode_gestion');
    }

    public function nature1()
    {
        return $this->belongsTo(\Nature1::class, 'IDNature1', 'IDNature1');
    }
}
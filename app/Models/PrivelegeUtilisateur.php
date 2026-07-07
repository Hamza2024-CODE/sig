<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivelegeUtilisateur extends Model
{
    protected $table      = 'privelege_utilisateur';
    protected $primaryKey = 'IDPrivelege_Utilisateur';
    public    $timestamps = false;

    protected $fillable = [
        'IDUtilisateur',
        'IDPrivelege',
        'DroiAjout',
        'DroiModif',
        'DroitSuppr',
        'DroitTous',
        'IDBureau',
        'IDMode_formation',
        'activee',
        'Code',
        'IDMode_gestion',
        'IDNature',
        'IDPrivelege_Utilisateur',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function privelege()
    {
        return $this->belongsTo(Privelege::class, 'IDPrivelege', 'IDPrivelege');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'IDUtilisateur', 'IDUtilisateur');
    }
}

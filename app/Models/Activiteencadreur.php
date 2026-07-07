<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activiteencadreur extends Model
{
    protected $table      = 'activiteencadreur';
    protected $primaryKey = 'IDActiviteEncadreur';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDActiviteEncadreurType',
        'Tel',
        'Email',
        'Obs',
        'FonctionGrade',
        'IDActivite',
        'Taches',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function activiteEncadreurType()
    {
        return $this->belongsTo(\ActiviteEncadreurType::class, 'IDActiviteEncadreurType', 'IDActiviteEncadreurType');
    }

    public function activite()
    {
        return $this->belongsTo(\Activite::class, 'IDActivite', 'IDActivite');
    }
}
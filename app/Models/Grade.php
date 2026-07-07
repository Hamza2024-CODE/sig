<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $table      = 'grade';
    protected $primaryKey = 'IDGrade';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDCorp',
        'Indice',
        'Num',
        'posteSup',
        'Dureestagetest',
        'stagetest',
        'NumOrd',
        'Dureetitularisation',
        'stagetesttitularisation',
        'DureeTest',
        'IDDiplome',
        'IDNiveau_Scol_enca',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function corp()
    {
        return $this->belongsTo(\Corp::class, 'IDCorp', 'IDCorp');
    }

    public function diplome()
    {
        return $this->belongsTo(\Diplome::class, 'IDDiplome', 'IDDiplome');
    }

    public function niveauScolEnca()
    {
        return $this->belongsTo(\NiveauScolEnca::class, 'IDNiveau_Scol_enca', 'IDNiveau_Scol_enca');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetencesActivitesProfessionnelle extends Model
{
    protected $table      = 'competences_activites_professionnelles';
    protected $primaryKey = 'IDCompetences_Activites_professionnelles';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDModule_Comp´┐¢tences',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function moduleComp´┐¢tence()
    {
        return $this->belongsTo(\ModuleComp´┐¢tence::class, 'IDModule_Comp´┐¢tences', 'IDModule_Comp´┐¢tences');
    }
}
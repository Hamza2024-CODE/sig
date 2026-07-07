<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetencesSavoirstheorique extends Model
{
    protected $table      = 'competences_savoirstheoriques';
    protected $primaryKey = 'IDCompetences_Savoirstheoriques';
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
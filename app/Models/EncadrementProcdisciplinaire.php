<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementProcdisciplinaire extends Model
{
    protected $table      = 'encadrement_procdisciplinaire';
    protected $primaryKey = 'IDEncadrement_Procdisciplinaire';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Motif',
        'IDEncadrement',
        'IDProcedure_Disciplinaire',
        'DateDecision',
        'Rationale',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function procedureDisciplinaire()
    {
        return $this->belongsTo(\ProcedureDisciplinaire::class, 'IDProcedure_Disciplinaire', 'IDProcedure_Disciplinaire');
    }
}
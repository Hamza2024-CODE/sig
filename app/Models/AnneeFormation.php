<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnneeFormation extends Model
{
    protected $table      = 'annee_formation';
    protected $primaryKey = 'IDAnnee_Formation';
    public    $timestamps = false;

    protected $fillable = [
        'CodeAnne',
        'Nom',
        'NomFr',
        'Encour',
        'NumOrd',
        'DateD',
        'DateF',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeCurrent($query)
    {
        return $query->where('Encour', 1)->orderByDesc('NumOrd')->limit(1);
    }

    public function scopeActive($query)
    {
        return $query->where('IDAnnee_Formation', '>', 0)->orderByDesc('NumOrd');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Privelege extends Model
{
    protected $table      = 'privelege';
    protected $primaryKey = 'IDPrivelege';
    public    $timestamps = false;

    protected $fillable = [
        'code',
        'nomFr',
        'NomAr',
        'Plan',
        'Phase',
        'Groupe',
        'PhaseSecond',
        'Obs',
        'activee',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function userOverrides()
    {
        return $this->hasMany(PrivelegeUtilisateur::class, 'IDPrivelege', 'IDPrivelege');
    }
}

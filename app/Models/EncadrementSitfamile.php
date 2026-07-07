<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementSitfamile extends Model
{
    protected $table      = 'encadrement_sitfamile';
    protected $primaryKey = 'IDEncadrement_SitFamile';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'DateD',
        'DateF',
        'nbrEnf',
        'Nbrenf10',
        'nbrenfscol',
        'IDSitfamille',
        'IDEncadrement',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sitfamille()
    {
        return $this->belongsTo(\Sitfamille::class, 'IDSitfamille', 'IDSitfamille');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}
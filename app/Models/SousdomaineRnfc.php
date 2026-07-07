<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SousdomaineRnfc extends Model
{
    protected $table      = 'sousdomaine_rnfc';
    protected $primaryKey = 'IDsousdomaine_rnfc';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDdomaine_rnfc',
        'code',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function domaineRnfc()
    {
        return $this->belongsTo(DomaineRnfc::class, 'IDdomaine_rnfc', 'IDdomaine_rnfc');
    }

    public function specialites()
    {
        return $this->hasMany(Specialite::class, 'IDsousdomaine_rnfc', 'IDsousdomaine_rnfc');
    }
}
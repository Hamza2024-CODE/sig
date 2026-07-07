<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activiteprogramme extends Model
{
    protected $table      = 'activiteprogramme';
    protected $primaryKey = 'IDActiviteProgramme';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Date',
        'heure',
        'IDActivite',
        'NumOrd',
        'jour',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function activite()
    {
        return $this->belongsTo(\Activite::class, 'IDActivite', 'IDActivite');
    }
}
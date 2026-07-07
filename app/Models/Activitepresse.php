<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activitepresse extends Model
{
    protected $table      = 'activitepresse';
    protected $primaryKey = 'IDActivitePresse';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDActivite',
        'IDActivitePresseType',
        'Lien',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function activite()
    {
        return $this->belongsTo(\Activite::class, 'IDActivite', 'IDActivite');
    }

    public function activitePresseType()
    {
        return $this->belongsTo(\ActivitePresseType::class, 'IDActivitePresseType', 'IDActivitePresseType');
    }
}
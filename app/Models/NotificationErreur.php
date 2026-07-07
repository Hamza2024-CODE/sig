<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationErreur extends Model
{
    protected $table      = 'notification_erreur';
    protected $primaryKey = 'IDnotification_erreur';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'IDetablissement',
        'IDDossier',
        'Num',
        'lu',
        'regler',
        'id',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function dossier()
    {
        return $this->belongsTo(\Dossier::class, 'IDDossier', 'IDDossier');
    }
}
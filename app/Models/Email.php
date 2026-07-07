<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $table      = 'email';
    protected $primaryKey = 'IDemail';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'objett',
        'contenu',
        'envoyer',
        'lire',
        'IDetablissement',
        'IDetablissement_dest',
        'Tel',
        'Date',
        'heure',
        'importace',
        'infoMail',
        'reponce',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function etablissementDest()
    {
        return $this->belongsTo(\EtablissementDest::class, 'IDetablissement_dest', 'IDetablissement_dest');
    }
}
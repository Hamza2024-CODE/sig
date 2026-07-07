<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatDocument extends Model
{
    protected $table      = 'candidat_document';
    protected $primaryKey = 'IDcandidat_document';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'relevedenotes_doc',
        'relevedenotes_url',
        'relevedenotes_url2',
        'IDCandidat',
        'enneexperience_doc',
        'enneexperience_url',
        'enneexperience_doc2',
        'exdiplome_doc',
        'exdiplome_url',
        'exdiplome_url2',
        'actn_doc',
        'actn_url',
        'actn_url2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function candidat()
    {
        return $this->belongsTo(\Candidat::class, 'IDCandidat', 'IDCandidat');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatContratapp extends Model
{
    protected $table      = 'candidat_contratapp';
    protected $primaryKey = 'IDCandidat_contratapp';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'photo',
        'IDCandidat',
        'url',
        'url2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function candidat()
    {
        return $this->belongsTo(\Candidat::class, 'IDCandidat', 'IDCandidat');
    }
}
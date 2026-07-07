<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatMemo extends Model
{
    protected $table      = 'candidat_memo';
    protected $primaryKey = 'IDCandidat_memo';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'photo',
        'IDCandidat',
        'url',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function candidat()
    {
        return $this->belongsTo(\Candidat::class, 'IDCandidat', 'IDCandidat');
    }
}
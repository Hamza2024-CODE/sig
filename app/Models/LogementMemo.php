<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogementMemo extends Model
{
    protected $table      = 'logement_memo';
    protected $primaryKey = 'IDLogement_memo';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'photo',
        'IDLogement',
        'Num',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function logement()
    {
        return $this->belongsTo(\Logement::class, 'IDLogement', 'IDLogement');
    }
}
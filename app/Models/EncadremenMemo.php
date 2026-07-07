<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadremenMemo extends Model
{
    protected $table      = 'encadremen_memo';
    protected $primaryKey = 'IDEncadremen_memo';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'photo',
        'Cv',
        'IDEncadrement',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}
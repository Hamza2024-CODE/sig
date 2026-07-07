<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dossier extends Model
{
    protected $table      = 'dossier';
    protected $primaryKey = 'IDDossier';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'IDParties',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function party()
    {
        return $this->belongsTo(\Party::class, 'IDParties', 'IDParties');
    }
}
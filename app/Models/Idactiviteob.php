<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Idactiviteob extends Model
{
    protected $table      = 'idactiviteobs';
    protected $primaryKey = 'IDIDActiviteObs';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Obs',
        'IDActivite',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function activite()
    {
        return $this->belongsTo(\Activite::class, 'IDActivite', 'IDActivite');
    }
}
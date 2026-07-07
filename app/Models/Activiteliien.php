<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activiteliien extends Model
{
    protected $table      = 'activiteliien';
    protected $primaryKey = 'IDActiviteliien';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Lien',
        'IDActivite',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function activite()
    {
        return $this->belongsTo(\Activite::class, 'IDActivite', 'IDActivite');
    }
}
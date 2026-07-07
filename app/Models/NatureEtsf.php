<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NatureEtsf extends Model
{
    protected $table      = 'nature_etsf';
    protected $primaryKey = 'IDNature_etsF';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'code',
        'NomES',
        'Abr',
        'AbrFr',
        'NumOrd',
        'IDNature',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function nature()
    {
        return $this->belongsTo(\Nature::class, 'IDNature', 'IDNature');
    }
}
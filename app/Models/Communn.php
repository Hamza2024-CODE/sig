<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Communn extends Model
{
    protected $table      = 'communn';
    protected $primaryKey = 'IDCommunn';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDWilayaa',
        'Nom',
        'NomFr',
        'Code',
        'CodeNouv',
        'NomEtat',
        'CODE_GEO',
        'code_miclat',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function wilayaa()
    {
        return $this->belongsTo(\Wilayaa::class, 'IDWilayaa', 'IDWilayaa');
    }
}
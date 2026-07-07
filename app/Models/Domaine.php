<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domaine extends Model
{
    protected $table      = 'domaine';
    protected $primaryKey = 'IDDomaine';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDBranche',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function branche()
    {
        return $this->belongsTo(\Branche::class, 'IDBranche', 'IDBranche');
    }
}
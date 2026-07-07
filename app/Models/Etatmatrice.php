<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etatmatrice extends Model
{
    protected $table      = 'etatmatrice';
    protected $primaryKey = 'IDEtatMatrice';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NbrFeuille',
        'Date',
        'IDBudget',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function budget()
    {
        return $this->belongsTo(\Budget::class, 'IDBudget', 'IDBudget');
    }
}
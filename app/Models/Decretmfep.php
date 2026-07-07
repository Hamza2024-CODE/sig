<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Decretmfep extends Model
{
    protected $table      = 'decretmfep';
    protected $primaryKey = 'IDDecretMfep';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'contenu',
        'IDDecretType',
        'DateDecret',
        'AnneDecret',
        'AnneJournal',
        'Numjounal',
        'DateJournal',
        'page',
        'IDDecret_Etat',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function decretType()
    {
        return $this->belongsTo(\DecretType::class, 'IDDecretType', 'IDDecretType');
    }

    public function decretEtat()
    {
        return $this->belongsTo(\DecretEtat::class, 'IDDecret_Etat', 'IDDecret_Etat');
    }
}
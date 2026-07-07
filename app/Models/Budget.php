<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $table      = 'budget';
    protected $primaryKey = 'IDBudget';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDannee',
        'IDTbudget',
        'IDDecret',
        'IDetablissement',
        'Encour',
        'code',
        'AE',
        'CP',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function annee()
    {
        return $this->belongsTo(\Annee::class, 'IDannee', 'IDannee');
    }

    public function tbudget()
    {
        return $this->belongsTo(\Tbudget::class, 'IDTbudget', 'IDTbudget');
    }

    public function decret()
    {
        return $this->belongsTo(\Decret::class, 'IDDecret', 'IDDecret');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}
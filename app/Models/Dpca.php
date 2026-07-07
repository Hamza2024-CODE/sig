<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dpca extends Model
{
    protected $table      = 'dpca';
    protected $primaryKey = 'IDDpca';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'N_visa',
        'Date_de_delivrance_du_visa',
        'Document',
        'Document1',
        'IDActions_Annee',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function actionsAnnee()
    {
        return $this->belongsTo(\ActionsAnnee::class, 'IDActions_Annee', 'IDActions_Annee');
    }
}
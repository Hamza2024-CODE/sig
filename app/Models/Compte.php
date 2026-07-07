<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compte extends Model
{
    protected $table      = 'compte';
    protected $primaryKey = 'IDCompte';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NomUtilisateur',
        'MotDePass',
        'CodeConf',
        'IDEts_Form',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etsForm()
    {
        return $this->belongsTo(\EtsForm::class, 'IDEts_Form', 'IDEts_Form');
    }
}
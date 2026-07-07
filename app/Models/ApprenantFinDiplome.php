<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprenantFinDiplome extends Model
{
    protected $table      = 'apprenant_fin_diplome';
    protected $primaryKey = 'IDApprenant_fin_diplome';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDApprenant_Fin',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenantFin()
    {
        return $this->belongsTo(\ApprenantFin::class, 'IDApprenant_Fin', 'IDApprenant_Fin');
    }
}
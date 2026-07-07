<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalLayout extends Model
{
    protected $table      = 'portal_layouts';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDUtilisateur',
        'portal_number',
        'layout_type',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function utilisateur()
    {
        return $this->belongsTo(\Utilisateur::class, 'IDUtilisateur', 'IDUtilisateur');
    }
}
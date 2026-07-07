<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sousaction extends Model
{
    protected $table      = 'sousaction';
    protected $primaryKey = 'IDSousAction';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'code',
        'IDAction',
        'NumOrd',
        'CodeComplet',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function action()
    {
        return $this->belongsTo(\Action::class, 'IDAction', 'IDAction');
    }
}
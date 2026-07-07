<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branche extends Model
{
    protected $table      = 'branche';
    protected $primaryKey = 'IDBranche';
    public    $timestamps = false;

    protected $fillable = [
        'Code',
        'Nom',
        'NomFr',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function specialites()
    {
        return $this->hasMany(Specialite::class, 'IDBranche', 'IDBranche');
    }
}

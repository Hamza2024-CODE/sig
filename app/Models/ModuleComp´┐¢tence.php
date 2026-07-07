<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleComp´┐¢tence extends Model
{
    protected $table      = 'module_comp´┐¢tences';
    protected $primaryKey = 'IDModule_Competences';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDModule',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function module()
    {
        return $this->belongsTo(\Module::class, 'IDModule', 'IDModule');
    }
}
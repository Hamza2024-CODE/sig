<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommandationsgenerale extends Model
{
    protected $table      = 'recommandationsgenerale';
    protected $primaryKey = 'IDRecommandations';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDActivit´┐¢',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function activit´┐¢()
    {
        return $this->belongsTo(\Activit´┐¢::class, 'IDActivit´┐¢', 'IDActivit´┐¢');
    }
}
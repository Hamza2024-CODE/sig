<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Daira extends Model
{
    protected $table      = 'daira';
    protected $primaryKey = 'IDdaira';
    public    $timestamps = false;

    protected $fillable = [
        'Nomdaira',
        'Codedaira',
        'IDWilayaa',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function wilaya()
    {
        return $this->belongsTo(Wilaya::class, 'IDWilayaa', 'IDWilayaa');
    }

    public function communes()
    {
        return $this->hasMany(Commune::class, 'Codedaira', 'Codedaira');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    protected $table      = 'commune';
    protected $primaryKey = 'IDcommune';
    public    $timestamps = false;

    protected $fillable = [
        'NomCommune',
        'Codecommune',
        'Codedaira',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function daira()
    {
        return $this->belongsTo(Daira::class, 'Codedaira', 'Codedaira');
    }

    public function wilaya()
    {
        // Commune belongs to Daira, which belongs to Wilaya.
        // We can access Wilaya via Daira relationship.
        return $this->daira ? $this->daira->wilaya() : null;
    }
}

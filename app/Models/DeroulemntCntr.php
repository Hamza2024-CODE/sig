<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeroulemntCntr extends Model
{
    protected $table      = 'deroulemnt_cntr';
    protected $primaryKey = 'IDderoulemnt_Cntr';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Date',
        'heurd',
        'heurf',
        'IDsection_semestre_Module',
        'IDLocaux',
        'IDEncadrement1',
        'IDEncadrement2',
        'IDEncadrement3',
        'IDEncadrement4',
        'IDEncadrement5',
        'IDLocaux1',
        'IDLocaux2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sectionSemestreModule()
    {
        return $this->belongsTo(\SectionSemestreModule::class, 'IDsection_semestre_Module', 'IDsection_semestre_Module');
    }

    public function locaux()
    {
        return $this->belongsTo(\Locaux::class, 'IDLocaux', 'IDLocaux');
    }

    public function encadrement1()
    {
        return $this->belongsTo(\Encadrement1::class, 'IDEncadrement1', 'IDEncadrement1');
    }

    public function encadrement2()
    {
        return $this->belongsTo(\Encadrement2::class, 'IDEncadrement2', 'IDEncadrement2');
    }

    public function encadrement3()
    {
        return $this->belongsTo(\Encadrement3::class, 'IDEncadrement3', 'IDEncadrement3');
    }

    public function encadrement4()
    {
        return $this->belongsTo(\Encadrement4::class, 'IDEncadrement4', 'IDEncadrement4');
    }

    public function encadrement5()
    {
        return $this->belongsTo(\Encadrement5::class, 'IDEncadrement5', 'IDEncadrement5');
    }

    public function locaux1()
    {
        return $this->belongsTo(\Locaux1::class, 'IDLocaux1', 'IDLocaux1');
    }

    public function locaux2()
    {
        return $this->belongsTo(\Locaux2::class, 'IDLocaux2', 'IDLocaux2');
    }
}
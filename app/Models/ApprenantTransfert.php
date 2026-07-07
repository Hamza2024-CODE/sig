<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprenantTransfert extends Model
{
    protected $table = 'apprenant_transferts';

    protected $fillable = [
        'apprenant_id',
        'from_etab_id',
        'to_etab_id',
        'to_section_id',
        'status',
        'rejection_comment',
        'sender_dfep_approved_by',
        'sender_dfep_approved_at',
        'receiver_approved_by',
        'receiver_approved_at',
        'receiver_dfep_approved_by',
        'receiver_dfep_approved_at',
    ];

    protected $casts = [
        'sender_dfep_approved_at' => 'datetime',
        'receiver_approved_at' => 'datetime',
        'receiver_dfep_approved_at' => 'datetime',
    ];

    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id', 'IDapprenant');
    }

    public function fromEtablissement()
    {
        return $this->belongsTo(Etablissement::class, 'from_etab_id', 'IDetablissement');
    }

    public function toEtablissement()
    {
        return $this->belongsTo(Etablissement::class, 'to_etab_id', 'IDetablissement');
    }

    public function toSection()
    {
        return $this->belongsTo(Section::class, 'to_section_id', 'IDSection');
    }
}

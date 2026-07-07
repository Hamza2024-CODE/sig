<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocumentRequest extends Model
{
    protected $table      = 'employee_document_requests';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'employee_id',
        'document_type',
        'code_verification',
        'status',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(\Employee::class, 'employee_id', 'employee_id');
    }
}
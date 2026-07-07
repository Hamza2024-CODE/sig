<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLeafe extends Model
{
    protected $table      = 'employee_leaves';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'leave_type',
        'reason',
        'status',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(\Employee::class, 'employee_id', 'employee_id');
    }
}
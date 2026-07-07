<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WorkflowRequest extends Model {
    protected $table = 'workflow_requests';
    protected $fillable = ['type','employee_id','employee_type','etablissement_id','wilaya_id','status','payload','motif','response_comment','approved_by','approved_at'];
    protected $casts   = ['payload' => 'array', 'approved_at' => 'datetime'];

    public function steps() { return $this->hasMany(WorkflowStep::class, 'request_id'); }

    public function latestStep() { return $this->hasOne(WorkflowStep::class, 'request_id')->latestOfMany(); }

    public function employee() {
        return $this->employee_type === 'encadrement'
            ? $this->belongsTo(Encadrement::class, 'employee_id', 'IDEncadrement')
            : $this->belongsTo(User::class, 'employee_id', 'IDUtilisateur');
    }

    public static function typeLabel(string $type): string {
        return match($type) {
            'conge'      => 'طلب إجازة',
            'promotion'  => 'طلب ترقية',
            'transfert'  => 'طلب تحويل',
            'formation'  => 'طلب تكوين',
            default      => $type,
        };
    }

    public static function statusBadge(string $status): string {
        return match($status) {
            'pending'   => '<span class="badge-pending">قيد الانتظار</span>',
            'approved'  => '<span class="badge-approved">مقبول</span>',
            'rejected'  => '<span class="badge-rejected">مرفوض</span>',
            'cancelled' => '<span class="badge-cancelled">ملغى</span>',
            default     => $status,
        };
    }
}
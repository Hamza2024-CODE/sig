<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EmployeeMessage extends Model {
    protected $table    = 'employee_messages';
    protected $fillable = ['sender_id','sender_type','receiver_id','receiver_type','channel','subject','body','priority','is_read','read_at','attachment_path'];
    protected $casts    = ['is_read' => 'boolean', 'read_at' => 'datetime'];

    public function sender() {
        return $this->sender_type === 'encadrement'
            ? $this->belongsTo(Encadrement::class, 'sender_id', 'IDEncadrement')
            : $this->belongsTo(User::class, 'sender_id', 'IDUtilisateur');
    }

    public function scopeForUser($query, int $userId) {
        return $query->where(function($q) use ($userId) {
            $q->where('receiver_id', $userId)->orWhere('sender_id', $userId)->orWhere('channel', 'broadcast');
        });
    }

    public function scopeUnread($query) { return $query->where('is_read', false); }

    public static function unreadCount(int $userId): int {
        return self::where('receiver_id', $userId)->where('is_read', false)->count();
    }

    public function priorityLabel(): string {
        return match($this->priority) {
            'urgent' => '🔴 عاجل',
            'high'   => '🟠 هام',
            'low'    => '🔵 عادي',
            default  => '⚪ عادي',
        };
    }
}
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessagingController extends Controller
{
    /** Inbox */
    public function index(Request $request)
    {
        $user    = session('user');
        $userId  = $user['id'] ?? 0;
        $role    = strtolower($user['role_code'] ?? '');
        $filter  = $request->get('filter', 'inbox');

        $query = EmployeeMessage::latest();

        match($filter) {
            'sent'      => $query->where('sender_id', $userId),
            'broadcast' => $query->where('channel', 'broadcast'),
            default     => $query->where(function($q) use ($userId) {
                $q->where('receiver_id', $userId)->orWhere('channel', 'broadcast');
            }),
        };

        $messages  = $query->paginate(20);
        $unread    = EmployeeMessage::unreadCount($userId);
        // جلب المكونين والمستخدمين مع مراعاة الحالات الفارغة (بيئات التطوير)
        $hasActiveEnc = DB::table('encadrement')->where('EtatActual', 1)->exists();
        $encQuery = DB::table('encadrement')
            ->select(DB::raw("IDEncadrement as id, CONCAT(Nom,' ',Prenom) as name, 'encadrement' as source"));
        if ($hasActiveEnc) {
            $encQuery->where('EtatActual', 1);
        }
        $encadrants = $encQuery->orderBy('Nom')->limit(250)->get();

        $hasActiveUsr = DB::table('utilisateur')->where('activee', 1)->exists();
        $usrQuery = DB::table('utilisateur')
            ->select(DB::raw("IDUtilisateur as id, CONCAT(Nom,' - ',NomUser) as name, 'utilisateur' as source"));
        if ($hasActiveUsr) {
            $usrQuery->where('activee', 1);
        }
        $utilisateurs = $usrQuery->orderBy('Nom')->limit(100)->get();

        // دمج المجموعتين وفرزهما
        $employees = $encadrants->merge($utilisateurs)
            ->filter(fn($e) => $e->id != $userId)
            ->sortBy('name')
            ->values();

        return view('dashboard.messaging.index', compact('messages','unread','filter','employees','role'));
    }

    /** Show a single message + mark as read */
    public function show(int $id)
    {
        $user   = session('user');
        $userId = $user['id'] ?? 0;
        $msg    = EmployeeMessage::findOrFail($id);

        // Mark as read
        if (!$msg->is_read && ($msg->receiver_id === $userId || $msg->channel === 'broadcast')) {
            $msg->update(['is_read' => true, 'read_at' => now()]);
        }

        return view('dashboard.messaging.show', compact('msg'));
    }

    /** Send a message */
    public function send(Request $request)
    {
        $user     = session('user');
        $senderId = $user['id'] ?? 0;
        $channel  = $request->input('channel', 'direct');

        EmployeeMessage::create([
            'sender_id'     => $senderId,
            'sender_type'   => $user['login_table'] ?? 'encadrement',
            'receiver_id'   => $channel === 'broadcast' ? null : $request->input('receiver_id'),
            'receiver_type' => 'encadrement',
            'channel'       => $channel,
            'subject'       => $request->input('subject', ''),
            'body'          => $request->input('body'),
            'priority'      => $request->input('priority', 'normal'),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'تم إرسال الرسالة بنجاح.']);
        }
        return back()->with('success', 'تم إرسال الرسالة بنجاح.');
    }

    /** Delete a message */
    public function destroy(int $id)
    {
        $user = session('user');
        $msg  = EmployeeMessage::findOrFail($id);
        if ($msg->sender_id === ($user['id'] ?? 0)) {
            $msg->delete();
        }
        return back()->with('success', 'تم حذف الرسالة.');
    }

    /** API: unread count */
    public function unreadCount(): \Illuminate\Http\JsonResponse
    {
        $user = session('user');
        return response()->json(['count' => EmployeeMessage::unreadCount($user['id'] ?? 0)]);
    }
}
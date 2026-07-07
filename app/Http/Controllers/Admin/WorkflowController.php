<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkflowRequest;
use App\Models\WorkflowStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Core\AuditLogger;

/**
 * WorkflowController — Gestion des demandes RH
 * Types: conge | promotion | transfert | formation
 * Statuses: pending | approved | rejected | cancelled
 */
class WorkflowController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = session('user');
            $role = strtolower($user['role_code'] ?? '');
            if (!in_array($role, ['admin', 'ministre', 'central'])) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
            }
            return $next($request);
        });
    }

    // ── Roles that can approve requests ──────────────────────────────────────
    protected array $approverRoles = ['admin','drh','dfep','directeur','high_admin','secretaire_general'];

    /**
     * List all requests (admin/approvers see all; employees see their own)
     */
    public function index(Request $request)
    {
        $user     = session('user');
        $role     = strtolower($user['role_code'] ?? '');
        $isAdmin  = in_array($role, $this->approverRoles);
        $query    = WorkflowRequest::with('latestStep')->latest();

        // Scope to own requests for regular employees
        if (!$isAdmin) {
            $empId = $user['id'] ?? 0;
            $query->where('employee_id', $empId);
        }

        // Filters
        if ($type = $request->get('type'))     $query->where('type', $type);
        if ($status = $request->get('status')) $query->where('status', $status);

        $requests  = $query->paginate(20);
        $stats     = $this->getStats($isAdmin, $user);

        return view('dashboard.workflow.index', compact('requests','stats','isAdmin','role'));
    }

    /**
     * Form to create a new request
     */
    public function create(Request $request)
    {
        $type = $request->get('type', 'conge');
        return view('dashboard.workflow.create', compact('type'));
    }

    /**
     * Store new request
     */
    public function store(Request $request)
    {
        $user    = session('user');
        $empId   = $user['id'] ?? 0;
        $type    = $request->input('type');

        $allowed = ['conge','promotion','transfert','formation'];
        if (!in_array($type, $allowed)) {
            return back()->with('error', 'نوع الطلب غير صالح.');
        }

        // Build type-specific payload
        $payload = match($type) {
            'conge'     => ['date_debut' => $request->date_debut, 'date_fin' => $request->date_fin, 'type_conge' => $request->type_conge],
            'promotion' => ['grade_actuel' => $request->grade_actuel, 'grade_demande' => $request->grade_demande, 'annees_exp' => $request->annees_exp],
            'transfert' => ['etab_actuel' => $request->etab_actuel, 'etab_demande' => $request->etab_demande, 'wilaya_demande' => $request->wilaya_demande],
            'formation' => ['intitule' => $request->intitule_formation, 'organisme' => $request->organisme, 'duree' => $request->duree],
            default     => [],
        };

        $req = WorkflowRequest::create([
            'type'             => $type,
            'employee_id'      => $empId,
            'employee_type'    => $user['login_table'] ?? 'encadrement',
            'etablissement_id' => $user['etablissement_id'] ?? null,
            'wilaya_id'        => $user['iddfep'] ?? null,
            'status'           => 'pending',
            'payload'          => $payload,
            'motif'            => $request->motif,
        ]);

        // Initial workflow step
        WorkflowStep::create([
            'request_id' => $req->id,
            'actor_role' => 'directeur',
            'action'     => null,
            'order'      => 1,
        ]);

        AuditLogger::log("WORKFLOW_CREATE_{$type}", 'workflow_requests', $req->id);

        // Notify approvers via notifications
        $this->notifyApprovers($req, $user);

        return redirect()->route('workflow.index')->with('success', 'تم إرسال طلبك بنجاح. رقم الطلب: #' . $req->id);
    }

    /**
     * Show a single request
     */
    public function show(int $id)
    {
        $user    = session('user');
        $role    = strtolower($user['role_code'] ?? '');
        $isAdmin = in_array($role, $this->approverRoles);

        $req = WorkflowRequest::with('steps')->findOrFail($id);

        // Non-admin can only view their own
        if (!$isAdmin && $req->employee_id != ($user['id'] ?? 0)) {
            return redirect()->route('workflow.index')->with('error', 'غير مصرح لك بعرض هذا الطلب.');
        }

        return view('dashboard.workflow.show', compact('req','isAdmin'));
    }

    /**
     * Approve or reject a request
     */
    public function decide(Request $request, int $id)
    {
        $user   = session('user');
        $role   = strtolower($user['role_code'] ?? '');

        if (!in_array($role, $this->approverRoles)) {
            return back()->with('error', 'ليس لديك صلاحية للبت في الطلبات.');
        }

        $req    = WorkflowRequest::findOrFail($id);
        $action = $request->input('action'); // approved | rejected

        if (!in_array($action, ['approved','rejected'])) {
            return back()->with('error', 'إجراء غير صالح.');
        }

        DB::transaction(function () use ($req, $action, $request, $user) {
            $req->update([
                'status'           => $action,
                'response_comment' => $request->input('comment'),
                'approved_by'      => $user['id'] ?? null,
                'approved_at'      => now(),
            ]);

            WorkflowStep::create([
                'request_id' => $req->id,
                'actor_role' => $user['role_code'] ?? 'admin',
                'actor_id'   => $user['id'] ?? null,
                'action'     => $action,
                'comment'    => $request->input('comment'),
                'order'      => $req->steps()->count() + 1,
            ]);
        });

        AuditLogger::log("WORKFLOW_{$action}", 'workflow_requests', $req->id);

        $label = $action === 'approved' ? 'قبول' : 'رفض';
        return redirect()->route('workflow.index')->with('success', "تم {$label} الطلب #" . $req->id . " بنجاح.");
    }

    /**
     * Cancel own request (if still pending)
     */
    public function cancel(int $id)
    {
        $user = session('user');
        $req  = WorkflowRequest::findOrFail($id);

        if ($req->employee_id != ($user['id'] ?? 0)) {
            return back()->with('error', 'غير مصرح.');
        }
        if ($req->status !== 'pending') {
            return back()->with('error', 'لا يمكن إلغاء طلب تمت معالجته.');
        }

        $req->update(['status' => 'cancelled']);
        return redirect()->route('workflow.index')->with('success', 'تم إلغاء طلبك بنجاح.');
    }

    // ── API for dashboard KPIs ────────────────────────────────────────────────
    public function apiStats(): \Illuminate\Http\JsonResponse
    {
        $user    = session('user');
        $role    = strtolower($user['role_code'] ?? '');
        $isAdmin = in_array($role, $this->approverRoles);
        return response()->json($this->getStats($isAdmin, $user));
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function getStats(bool $isAdmin, array $user): array
    {
        $q = WorkflowRequest::query();
        if (!$isAdmin) {
            $q->where('employee_id', $user['id'] ?? 0);
        }
        return [
            'total'    => (clone $q)->count(),
            'pending'  => (clone $q)->where('status','pending')->count(),
            'approved' => (clone $q)->where('status','approved')->count(),
            'rejected' => (clone $q)->where('status','rejected')->count(),
            'conge'    => (clone $q)->where('type','conge')->count(),
            'promotion'=> (clone $q)->where('type','promotion')->count(),
            'transfert'=> (clone $q)->where('type','transfert')->count(),
        ];
    }

    private function notifyApprovers(WorkflowRequest $req, array $user): void
    {
        try {
            $typeLabel = WorkflowRequest::typeLabel($req->type);
            DB::table('notifications')->insert([
                'user_id'    => null, // broadcast to role
                'role_target'=> 'directeur',
                'type'       => 'workflow',
                'title'      => "طلب {$typeLabel} جديد",
                'body'       => "قدّم الموظف {$user['nom_complet']} طلب {$typeLabel}. رقم الطلب: #{$req->id}",
                'data'       => json_encode(['request_id' => $req->id, 'type' => $req->type]),
                'is_read'    => 0,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Notifications table may have different schema
        }
    }
}
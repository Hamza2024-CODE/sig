<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'user_type',
        'title',
        'message',
        'type',
        'url',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Generate dynamic system notifications (absences, budgets) for the logged-in user.
     */
    public static function generateSystemNotifications($user): void
    {
        if (!$user) return;

        $userId = (int)($user['id'] ?? 0);
        $userType = strtolower($user['role_code'] ?? 'user');

        // 1. Scan for Trainee High Absences (NbrAbsences >= 15)
        try {
            $absentTraineesQuery = DB::table('apprenant')
                ->join('candidat', 'apprenant.IDCandidat', '=', 'candidat.IDCandidat')
                ->join('section', 'apprenant.IDSection', '=', 'section.IDSection')
                ->join('offre', 'section.IDOffre', '=', 'offre.IDOffre')
                ->select(
                    'apprenant.IDapprenant',
                    'candidat.Nom as nom',
                    'candidat.Prenom as prenom',
                    'apprenant.NbrAbsences as absences',
                    'offre.IDEts_Form as etab_id'
                )
                ->where('apprenant.NbrAbsences', '>=', 15);

            if ($userType === 'etablissement') {
                $absentTraineesQuery->where('offre.IDEts_Form', $userId);
            }

            $absentTrainees = $absentTraineesQuery->get();

            foreach ($absentTrainees as $trainee) {
                $title = 'غياب متكرر للمتربص: ' . $trainee->prenom . ' ' . $trainee->nom;
                $message = "تجاوز المتربص {$trainee->prenom} {$trainee->nom} الحد الأقصى للغيابات المسموح بها برصيد {$trainee->absences} غياب. يرجى إرسال إعذار رسمي له.";
                $url = request()->is('sig/*') || request()->is('sig')
                    ? '/sig/dashboard/candidates/show/' . $trainee->IDapprenant
                    : '/dashboard/candidates/show/' . $trainee->IDapprenant;

                // Check if an unread notification already exists to prevent duplication
                $exists = self::where('user_id', $userId)
                    ->where('user_type', $userType)
                    ->where('title', $title)
                    ->where('is_read', false)
                    ->exists();

                if (!$exists) {
                    self::create([
                        'user_id' => $userType === 'etablissement' ? $userId : null, // null means global/visible to admins
                        'user_type' => $userType === 'etablissement' ? 'etablissement' : 'admin',
                        'title' => $title,
                        'message' => $message,
                        'type' => 'danger',
                        'url' => $url,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Notification absences check failed: ' . $e->getMessage());
        }

        // 2. Scan for Low Budgets (CP < 100,000)
        try {
            $lowBudgetsQuery = DB::table('budget')
                ->select('IDBudget', 'Nom', 'CP', 'IDetablissement')
                ->where('CP', '<', 100000)
                ->where('Encour', 1);

            if ($userType === 'etablissement') {
                $lowBudgetsQuery->where('IDetablissement', $userId);
            }

            $lowBudgets = $lowBudgetsQuery->get();

            foreach ($lowBudgets as $budget) {
                $formattedCp = number_format($budget->CP, 2);
                $title = 'عجز ميزانية: ' . $budget->Nom;
                $message = "رصيد الدفع (CP) لبند الميزانية \"{$budget->Nom}\" منخفض جداً حالياً ويبلغ {$formattedCp} د.ج. يرجى طلب تمويل إضافي لتفادي توقف الدفع.";
                $url = request()->is('sig/*') || request()->is('sig')
                    ? '/sig/dashboard/finance'
                    : '/dashboard/finance';

                $exists = self::where('user_id', $userId)
                    ->where('user_type', $userType)
                    ->where('title', $title)
                    ->where('is_read', false)
                    ->exists();

                if (!$exists) {
                    self::create([
                        'user_id' => $userType === 'etablissement' ? $userId : null,
                        'user_type' => $userType === 'etablissement' ? 'etablissement' : 'admin',
                        'title' => $title,
                        'message' => $message,
                        'type' => 'warning',
                        'url' => $url,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Notification budget check failed: ' . $e->getMessage());
        }
    }
}
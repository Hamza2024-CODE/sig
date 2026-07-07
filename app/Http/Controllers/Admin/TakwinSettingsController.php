<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\TakwinHelper;
use App\Helpers\EncryptionHelper;
use App\Core\AuditLogger;

class TakwinSettingsController extends Controller
{
    public function __construct()
    {
        if (app()->runningInConsole()) { return; }

        $this->middleware(function ($request, $next) {
            // Allow admin, high-level admins (IG, SG), and DFEP supervisors
            $roleCode = strtolower(session('user')['role_code'] ?? '');
            if (!in_array($roleCode, ['admin', 'high_admin', 'dfep'])) {
                session(['flash_error' => 'غير مسموح لك بالوصول إلى هذه الصفحة / Accès refusé.']);
                return redirect('/dashboard');
            }
            return $next($request);
        });
    }

    /**
     * Show the Takwin API integration settings page
     */
    public function index()
    {
        $settings = TakwinHelper::getSettings();
        
        return $this->render('admin/settings/takwin', [
            'title' => 'إعدادات المزامنة وتكامل البيانات (Takwin API) / Intégration',
            'settings' => $settings
        ]);
    }

    /**
     * Update settings
     */
    public function update()
    {
        if (request()->isMethod('post')) {
            // Verify CSRF Token
            $csrfToken = request()->all()['csrf_token'] ?? '';
            if (empty($csrfToken) || $csrfToken !== (csrf_token() ?? '')) {
                session(['flash_error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً / Jeton CSRF invalide.']);
                return $this->redirect('/dashboard/settings/takwin');
            }

            $apiUrl = trim(request()->all()['api_url'] ?? '');
            $apiToken = trim(request()->all()['api_token'] ?? '');
            $syncEnabled = isset(request()->all()['sync_enabled']) ? 1 : 0;

            if (empty($apiUrl)) {
                session(['flash_error' => 'يرجى إدخال عنوان رابط API صالح / URL de l\'API requis.']);
                return $this->redirect('/dashboard/settings/takwin');
            }

            try {
                $oldSettings = TakwinHelper::getSettings();

                // If user didn't modify the API token (still displays masked/empty or kept as asterisks), keep the old one
                if (empty($apiToken) || $apiToken === str_repeat('*', strlen($apiToken)) || $apiToken === '—' || strpos($apiToken, '...') !== false) {
                    $apiToken = $oldSettings['api_token'];
                }

                $success = TakwinHelper::saveSettings($apiUrl, $apiToken, $syncEnabled);

                if ($success) {
                    AuditLogger::log('UPDATE', 'takwin_settings', $oldSettings['id'] ?? 1, [
                        'api_url' => $oldSettings['api_url'],
                        'sync_enabled' => $oldSettings['sync_enabled']
                    ], [
                        'api_url' => $apiUrl,
                        'sync_enabled' => $syncEnabled
                    ]);

                    session(['flash_success' => 'تم حفظ إعدادات الربط وتكامل البيانات بنجاح! / Paramètres enregistrés avec succès.']);
                } else {
                    session(['flash_error' => 'فشل حفظ الإعدادات في قاعدة البيانات.']);
                }
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ غير متوقع: ' . $e->getMessage()]);
            }
        }

        return $this->redirect('/dashboard/settings/takwin');
    }

    /**
     * Show the Diploma Customization Settings page
     */
    public function diplome()
    {
        $settings = \App\Helpers\TakwinHelper::getSettings();
        
        return $this->render('admin/settings/diplome', [
            'title' => 'تخصيص وضبط الشهادات الرسمية / Personnalisation',
            'settings' => $settings
        ]);
    }

    /**
     * Update Diploma Customization Settings
     */
    public function updateDiploma()
    {
        if (request()->isMethod('post')) {
            // Verify CSRF Token
            $csrfToken = request()->all()['csrf_token'] ?? '';
            if (empty($csrfToken) || $csrfToken !== (csrf_token() ?? '')) {
                session(['flash_error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً / Jeton CSRF invalide.']);
                return $this->redirect('/dashboard/settings/diplome');
            }

            $bgUrl = trim(request()->all()['diploma_bg_url'] ?? '');
            $borderColor = trim(request()->all()['diploma_border_color'] ?? '#1e3a8a');
            $watermarkUrl = trim(request()->all()['diploma_watermark_url'] ?? '');
            $primaryColor = trim(request()->all()['diploma_primary_color'] ?? '#1e3a8a');

            try {
                $success = TakwinHelper::saveDiplomaSettings($bgUrl, $borderColor, $watermarkUrl, $primaryColor);

                if ($success) {
                    session(['flash_success' => 'تم حفظ إعدادات تخصيص الشهادة بنجاح! / Paramètres du diplôme enregistrés.']);
                } else {
                    session(['flash_error' => 'فشل حفظ إعدادات الشهادة في قاعدة البيانات.']);
                }
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ غير متوقع: ' . $e->getMessage()]);
            }
        }

        return $this->redirect('/dashboard/settings/diplome');
    }

    /**
     * Trigger manual synchronization
     */
    public function sync()
    {
        if (request()->isMethod('post')) {
            // Verify CSRF Token
            $csrfToken = request()->all()['csrf_token'] ?? '';
            if (empty($csrfToken) || $csrfToken !== (csrf_token() ?? '')) {
                session(['flash_error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً / Jeton CSRF invalide.']);
                return $this->redirect('/dashboard/settings/takwin');
            }

            $simulate = isset(request()->all()['simulate']) && request()->all()['simulate'] == 1;

            try {
                $result = TakwinHelper::syncCandidates($simulate);

                if ($result['success']) {
                    session(['flash_success' => $result['message']]);
                } else {
                    session(['flash_error' => $result['message']]);
                }
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ أثناء المزامنة: ' . $e->getMessage()]);
            }
        }

        return $this->redirect('/dashboard/settings/takwin');
    }
}

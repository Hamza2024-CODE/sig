<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\Admin\OffresController;
use App\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| SGFEP – Web Routes
|--------------------------------------------------------------------------
|
| Architecture: Strangler-Fig (Progression vers Laravel natif 100%)
|
| L'ordre de migration:
|   ✅ Auth         → Laravel 100% natif
|   ✅ Dashboard    → Laravel 100% natif (3-level cache)
|   ✅ Profile      → Laravel 100% natif
|   🔄 Les autres  → Legacy Bridge (migration en cours)
|
| Session Bridge:
|   StartSession middleware gère la session PHP.
|   LegacyBridgeController hydrate $_SESSION avant d'appeler le legacy.
|
| */

// ── TWA Digital Asset Links (Android Trusted Web Activity) ─────────────────
Route::get('/.well-known/assetlinks.json', function () {
    return response()->file(public_path('.well-known/assetlinks.json'), [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('assetlinks');

// ═══════════════════════════════════════════════════════════════════════════
// §1  ROUTES PUBLIQUES (sans authentification)
// ═══════════════════════════════════════════════════════════════════════════

// ⛔ [SECURITY] /auto-login route has been PERMANENTLY DISABLED (was a Critical vulnerability).
// Route::get('/auto-login', ...) — removed: gave unauthenticated users instant Admin access.

Route::get('/',              [HomeController::class, 'index']);
Route::get('/resultats',     [HomeController::class, 'searchResult']);
Route::get('/verify-diploma', [HomeController::class, 'verifyDiploma'])->name('diplomes.public.verify');
Route::get('/portal/{page}', [PortalController::class, 'renderPage']);
Route::get('/verify/card/employee/{hash}', [\App\Http\Controllers\PortalController::class, 'publicVerifyEmployeeCard'])->name('card.verify.employee');
Route::get('/verify/card/trainee/{hash}', [\App\Http\Controllers\PortalController::class, 'publicVerifyTraineeCard'])->name('card.verify.trainee');
Route::match(['get','post'], '/verify', [\App\Http\Controllers\Admin\ModulesController::class, 'publicVerifyDocument'])->name('document.public_verify');
Route::get('/verify/print/{id}', [\App\Http\Controllers\Admin\ModulesController::class, 'publicPrintDocument'])->name('document.public_print');

// Local Offline QR Code API Endpoint (Solves CSP and Intra-net access blocks)
Route::get('/api/qrcode', function (\Illuminate\Http\Request $request) {
    $data = $request->query('data', '');
    if (empty($data)) {
        return response('', 400);
    }
    try {
        $qrCode = (new \chillerlan\QRCode\QRCode)->render($data);
        if (str_starts_with($qrCode, 'data:')) {
            $parts = explode(',', $qrCode);
            $meta = explode(';', $parts[0]);
            $mime = str_replace('data:', '', $meta[0]);
            $content = base64_decode($parts[1]);
            return response($content)->header('Content-Type', $mime);
        }
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Local API QR Code generation failed: ' . $e->getMessage());
        return response('', 500);
    }
    return response('', 400);
});

// Auth (100% Laravel)
Route::get('/login',                     [LoginController::class, 'showLoginFormView'])->name('login');
Route::post('/login',                    [LoginController::class, 'login'])->middleware('throttle:login');
Route::match(['get','post'], '/logout',  [LoginController::class, 'logout'])->name('logout');
Route::post('/login/get-employee-code',  [LoginController::class, 'getEmployeeCode']);

// OAuth / SSO (Microsoft Azure AD, Google Workspace)
Route::get('/auth/oauth/{provider}/redirect',  [\App\Http\Controllers\Auth\OAuthController::class, 'redirect'])
    ->middleware('throttle:10,1');
Route::get('/auth/oauth/{provider}/callback',  [\App\Http\Controllers\Auth\OAuthController::class, 'callback'])
    ->middleware('throttle:10,1');

// ── /sig prefix mirrors for XAMPP subfolder access ────────────────────────
Route::prefix('sig')->group(function () {
    // ⛔ [SECURITY] /sig/auto-login route has been PERMANENTLY DISABLED (Critical vulnerability removed).

    Route::get('/verify/card/employee/{hash}', [\App\Http\Controllers\PortalController::class, 'publicVerifyEmployeeCard']);
    Route::get('/verify/card/trainee/{hash}', [\App\Http\Controllers\PortalController::class, 'publicVerifyTraineeCard']);
    Route::get('/resultats',     [HomeController::class, 'searchResult']);
    Route::get('/portal/{page}', [PortalController::class, 'renderPage']);
    Route::match(['get','post'], '/verify', [\App\Http\Controllers\Admin\ModulesController::class, 'publicVerifyDocument']);
    Route::get('/verify/print/{id}', [\App\Http\Controllers\Admin\ModulesController::class, 'publicPrintDocument']);
    Route::get('/login',         [LoginController::class, 'showLoginFormView']);
    Route::post('/login',        [LoginController::class, 'login'])->middleware('throttle:login');
    Route::match(['get','post'], '/logout', [LoginController::class, 'logout']);
    Route::post('/login/get-employee-code', [LoginController::class, 'getEmployeeCode']);

    // Local Offline QR Code API Endpoint for sig subfolder
    Route::get('/api/qrcode', function (\Illuminate\Http\Request $request) {
        $data = $request->query('data', '');
        if (empty($data)) {
            return response('', 400);
        }
        try {
            $qrCode = (new \chillerlan\QRCode\QRCode)->render($data);
            if (str_starts_with($qrCode, 'data:')) {
                $parts = explode(',', $qrCode);
                $meta = explode(';', $parts[0]);
                $mime = str_replace('data:', '', $meta[0]);
                $content = base64_decode($parts[1]);
                return response($content)->header('Content-Type', $mime);
            }
        } catch (\Throwable $e) {
            return response('', 500);
        }
        return response('', 400);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// §2  ROUTES PROTÉGÉES — Laravel natif 100%
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware('check.session')->group(function () {
    Route::get('/session/check-active', [\App\Http\Controllers\Auth\LoginController::class, 'checkActiveSession'])->name('session.check-active');

    // ── Licensing Shield Page (accessible when logged in, but not activated) ──
    Route::get('/activate', [\App\Http\Controllers\Auth\ActivationController::class, 'showShield'])->name('activation.shield');
    Route::post('/activate', [\App\Http\Controllers\Auth\ActivationController::class, 'activate']);

    Route::middleware(['activation.check', 'mfa.check'])->group(function () {

        // ── Profil ────────────────────────────────────────────────────────────
    Route::get('/debug-my-session', function() {
        return response()->json(session('user'));
    });
    Route::get('/dashboard/profile',         [ProfileController::class, 'index']);
    Route::post('/dashboard/profile/update', [ProfileController::class, 'update']);

    // ── Etablissement Profile ─────────────────────────────────────────────
    Route::get('/dashboard/etablissement',         [\App\Http\Controllers\Admin\EtablissementController::class, 'show'])->name('etablissement.show');
    Route::post('/dashboard/etablissement/update', [\App\Http\Controllers\Admin\EtablissementController::class, 'update'])->name('etablissement.update');

    // ── Dashboard principal (Laravel — 3-level cache) ─────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats/api', [DashboardController::class, 'statsApi'])->name('dashboard.stats.api');
    Route::post('/dashboard/stats/refresh', [DashboardController::class, 'statsRefresh'])->name('dashboard.stats.refresh');

    // ── Global Search (role-scoped cross-entity search) ───────────────────
    Route::match(['get','post'], '/dashboard/search', [\App\Http\Controllers\GlobalSearchController::class, 'search'])
        ->name('dashboard.search')
        ->middleware('throttle:60,1'); // 60 requests per minute

    // ── SSE Real-Time KPI Stream ──────────────────────────────────────────
    Route::get('/dashboard/kpi-stream', [\App\Http\Controllers\KpiStreamController::class, 'stream'])
        ->name('dashboard.kpi-stream')
        ->middleware('throttle:sse');

    // ── Push Notifications (VAPID) ────────────────────────────────────────
    Route::post('/push/subscribe',   [\App\Http\Controllers\PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [\App\Http\Controllers\PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::get('/push/vapid-key',    [\App\Http\Controllers\PushController::class, 'vapidPublicKey'])->name('push.vapid-key');

    // ── HFSQL Sync Extractor ───────────────────────────────────────────────
    Route::get('/dashboard/sync-files', [\App\Http\Controllers\Admin\FileSyncController::class, 'index'])->name('admin.sync-files.index');
    Route::get('/dashboard/sync-files/stats', [\App\Http\Controllers\Admin\FileSyncController::class, 'statsApi'])->name('admin.sync-files.stats');
    Route::post('/dashboard/sync-files/process', [\App\Http\Controllers\Admin\FileSyncController::class, 'process'])->name('admin.sync-files.process');
    Route::get('/dashboard/sync-files/etablissements', [\App\Http\Controllers\Admin\FileSyncController::class, 'getEtablissements'])->name('admin.sync-files.etablissements');
    Route::post('/dashboard/sync-files/start', [\App\Http\Controllers\Admin\FileSyncController::class, 'startSync'])->name('admin.sync-files.start');
    Route::get('/dashboard/sync-files/sync-status', [\App\Http\Controllers\Admin\FileSyncController::class, 'syncStatus'])->name('admin.sync-files.status');

    // ── Direct routes for department dashboards ───────────────────────────
    Route::get('/dashboard/finance', [DashboardController::class, 'viewFinance']);
    Route::get('/dashboard/rh',      [DashboardController::class, 'viewRh']);
    Route::get('/dashboard/plan',    [DashboardController::class, 'viewPlan']);
    Route::get('/dashboard/coop',    [DashboardController::class, 'viewCoop']);
    Route::get('/dashboard/it',      [DashboardController::class, 'viewIt']);
    Route::get('/dashboard/exam',    [DashboardController::class, 'viewExam']);
    Route::post('/dashboard/exam/add-session', [DashboardController::class, 'addExamSession']);
    Route::get('/dashboard/trak',    [DashboardController::class, 'viewTrak']);
    Route::get('/dashboard/org',     [DashboardController::class, 'viewTrak']); // Map org to trak
    Route::get('/dashboard/edu',     [DashboardController::class, 'viewEdu']);
    Route::get('/dashboard/dfcri',   [DashboardController::class, 'viewDfcri']);
    Route::get('/dashboard/promotions', [DashboardController::class, 'viewPromotions']);
    Route::get('/dashboard/concours',   [DashboardController::class, 'viewConcours']);
    Route::get('/dashboard/salaires',   [DashboardController::class, 'viewSalaires']);
    Route::get('/dashboard/admin-stats', [DashboardController::class, 'viewAdminStats'])->name('admin.stats');

    // ── 🔄 Workflow Engine (Congés / Promotions / Transferts) ────────────────
    Route::prefix('dashboard/workflow')->name('workflow.')->group(function () {
        Route::get('/',                       [\App\Http\Controllers\Admin\WorkflowController::class, 'index'])->name('index');
        Route::get('/create',                 [\App\Http\Controllers\Admin\WorkflowController::class, 'create'])->name('create');
        Route::post('/store',                 [\App\Http\Controllers\Admin\WorkflowController::class, 'store'])->name('store');
        Route::get('/{id}',                   [\App\Http\Controllers\Admin\WorkflowController::class, 'show'])->name('show');
        Route::post('/{id}/decide',           [\App\Http\Controllers\Admin\WorkflowController::class, 'decide'])->name('decide');
        Route::post('/{id}/cancel',           [\App\Http\Controllers\Admin\WorkflowController::class, 'cancel'])->name('cancel');
        Route::get('/api/stats',              [\App\Http\Controllers\Admin\WorkflowController::class, 'apiStats'])->name('stats');
    });

    // ── 💬 Internal Messaging System ─────────────────────────────────────────
    Route::prefix('dashboard/messages')->name('messages.')->group(function () {
        Route::get('/',                       [\App\Http\Controllers\Admin\MessagingController::class, 'index'])->name('index');
        Route::get('/{id}',                   [\App\Http\Controllers\Admin\MessagingController::class, 'show'])->name('show');
        Route::post('/send',                  [\App\Http\Controllers\Admin\MessagingController::class, 'send'])->name('send');
        Route::delete('/{id}',               [\App\Http\Controllers\Admin\MessagingController::class, 'destroy'])->name('destroy');
        Route::get('/api/unread',             [\App\Http\Controllers\Admin\MessagingController::class, 'unreadCount'])->name('unread');
    });

    // ── 🔗 External Integrations API ─────────────────────────────────────────
    Route::prefix('dashboard/integrations')->group(function () {
        Route::get('/anem/verify/{nin}', function (string $nin) {
            return response()->json(\App\Services\Integrations\ExternalIntegrationService::anemVerifyNin($nin));
        });
        Route::get('/cnas/verify/{nin}', function (string $nin) {
            return response()->json(\App\Services\Integrations\ExternalIntegrationService::cnasVerify($nin));
        });
        Route::get('/damancom/contributions/{nin}', function (string $nin) {
            return response()->json(\App\Services\Integrations\ExternalIntegrationService::damancomContributions($nin));
        });
    });

    // Promotions CRUD
    Route::post('/dashboard/promotions/store', [DashboardController::class, 'storePromotion']);
    Route::post('/dashboard/promotions/update', [DashboardController::class, 'updatePromotion']);
    Route::post('/dashboard/promotions/delete/{id}', [DashboardController::class, 'deletePromotion']);

    // Concours CRUD
    Route::post('/dashboard/concours/store', [DashboardController::class, 'storeConcours']);
    Route::post('/dashboard/concours/update', [DashboardController::class, 'updateConcours']);
    Route::post('/dashboard/concours/delete/{id}', [DashboardController::class, 'deleteConcours']);

    // Salaries CRUD
    Route::post('/dashboard/salaires/store', [DashboardController::class, 'storeSalaire']);
    Route::post('/dashboard/salaires/update', [DashboardController::class, 'updateSalaire']);
    Route::post('/dashboard/salaires/delete/{id}', [DashboardController::class, 'deleteSalaire']);

    // ── Central Dashboards Database Actions ────────────────────────────────
    Route::post('/dashboard/finance/add-budget', [DashboardController::class, 'addBudget']);
    Route::post('/dashboard/finance/update-budget', [DashboardController::class, 'updateBudget']);
    Route::post('/dashboard/finance/delete-budget/{id}', [DashboardController::class, 'deleteBudget']);

    Route::post('/dashboard/finance/add-operation', [DashboardController::class, 'addOperation']);
    Route::post('/dashboard/finance/update-operation', [DashboardController::class, 'updateOperation']);
    Route::post('/dashboard/finance/delete-operation/{id}', [DashboardController::class, 'deleteOperation']);

    Route::post('/dashboard/finance/send-notification', [DashboardController::class, 'sendFinanceNotification']);
    Route::post('/dashboard/it/add-api-key',      [DashboardController::class, 'addApiKey']);
    Route::post('/dashboard/plan/add-project',   [DashboardController::class, 'addProject']);
    Route::post('/dashboard/coop/add-agreement', [DashboardController::class, 'addAgreement']);
    Route::post('/dashboard/dfcri/add-partner',  [DashboardController::class, 'addPartner']);
    Route::post('/dashboard/edu/add-specialty',  [DashboardController::class, 'addSpecialty']);
    Route::post('/dashboard/exam/add-session',   [DashboardController::class, 'addSession']);
    Route::post('/dashboard/trak/add-course',    [DashboardController::class, 'addCourse']);
    Route::post('/dashboard/rh/add-employee',    [DashboardController::class, 'addEmployee']);
    Route::post('/dashboard/central/add-study',  [DashboardController::class, 'addStudy']);

    // ── Offres (100% Laravel) ─────────────────────────────────────────────
    Route::prefix('dashboard/offres')->group(function () {
        Route::get('/',                     [OffresController::class, 'index'])->name('offres.index');
        Route::get('/validation',           [OffresController::class, 'validation'])->name('offres.validation');
        Route::get('/print',                [OffresController::class, 'printOffres'])->name('offres.print');
        Route::post('/store',               [OffresController::class, 'storeOffre'])->name('offres.store');
        Route::post('/update',              [OffresController::class, 'updateOffre'])->name('offres.update');
        Route::post('/delete/{id}',         [OffresController::class, 'deleteOffre'])->name('offres.delete');
        Route::post('/soumettre',           [OffresController::class, 'soumettreOffre'])->name('offres.soumettre');
        Route::post('/valider-direction',   [OffresController::class, 'validerDirection'])->name('offres.valider-direction');
        Route::post('/valider-centrale',    [OffresController::class, 'validerCentrale'])->name('offres.valider-centrale');
    });

    // ── 📊 Dynamic Report Builder ─────────────────────────────────────────
    Route::prefix('dashboard/reports')->name('reports.')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
        Route::get('/export/{type}', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('export');
        Route::get('/print/{type}',  [\App\Http\Controllers\Admin\ReportController::class, 'printReport'])->name('print');
        Route::get('/pdf/{type}',    [\App\Http\Controllers\Admin\ReportController::class, 'pdfReport'])->name('pdf');
    });

    // ── 📊 Pedagogical Activity Report ─────────────────────────────────────
    Route::get('/dashboard/pedagogical-activity-report', [\App\Http\Controllers\Admin\PedagogicalActivityReportController::class, 'index'])->name('pedagogical-activity-report.index');
    Route::get('/dashboard/pedagogical-activity-report/export', [\App\Http\Controllers\Admin\PedagogicalActivityReportController::class, 'exportExcel'])->name('pedagogical-activity-report.export');
    Route::get('/dashboard/pedagogical-activity-report/section-trainees', [\App\Http\Controllers\Admin\PedagogicalActivityReportController::class, 'getSectionTrainees'])->name('pedagogical-activity-report.section-trainees');

    // ── التسيير المالي (Finances) — CRUD + Print ──────────────────────────
    Route::prefix('dashboard/finances')->group(function () {
        Route::get('/',                             [\App\Http\Controllers\Admin\FinancesController::class, 'index'])->name('finances.index');
        // Grades / Postes Budgétaires
        Route::post('/grades/store',                [\App\Http\Controllers\Admin\FinancesController::class, 'storeGrade'])->name('finances.grades.store');
        Route::post('/grades/update',               [\App\Http\Controllers\Admin\FinancesController::class, 'updateGrade'])->name('finances.grades.update');
        Route::post('/grades/delete/{id}',          [\App\Http\Controllers\Admin\FinancesController::class, 'deleteGrade'])->name('finances.grades.delete');
        Route::get('/grades/print',                 [\App\Http\Controllers\Admin\FinancesController::class, 'printGrades'])->name('finances.grades.print');
        // Programmes & Sous-Programmes
        Route::post('/programmes/store',            [\App\Http\Controllers\Admin\FinancesController::class, 'storeProgramme'])->name('finances.programmes.store');
        Route::post('/programmes/update',           [\App\Http\Controllers\Admin\FinancesController::class, 'updateProgramme'])->name('finances.programmes.update');
        Route::post('/programmes/delete/{id}',      [\App\Http\Controllers\Admin\FinancesController::class, 'deleteProgramme'])->name('finances.programmes.delete');
        Route::get('/programmes/print',             [\App\Http\Controllers\Admin\FinancesController::class, 'printProgrammes'])->name('finances.programmes.print');
        Route::post('/sous-programmes/store',       [\App\Http\Controllers\Admin\FinancesController::class, 'storeSousProgramme'])->name('finances.sousprogrammes.store');
        Route::post('/sous-programmes/update',      [\App\Http\Controllers\Admin\FinancesController::class, 'updateSousProgramme'])->name('finances.sousprogrammes.update');
        Route::post('/sous-programmes/delete/{id}', [\App\Http\Controllers\Admin\FinancesController::class, 'deleteSousProgramme'])->name('finances.sousprogrammes.delete');
        // Fournisseurs
        Route::post('/fournisseurs/store',          [\App\Http\Controllers\Admin\FinancesController::class, 'storeFournisseur'])->name('finances.fournisseurs.store');
        Route::post('/fournisseurs/update',         [\App\Http\Controllers\Admin\FinancesController::class, 'updateFournisseur'])->name('finances.fournisseurs.update');
        Route::post('/fournisseurs/delete/{id}',    [\App\Http\Controllers\Admin\FinancesController::class, 'deleteFournisseur'])->name('finances.fournisseurs.delete');
        // Extended Budgets, Operations, Bourses, Stocks, Profile
        Route::post('/budget/store',                [\App\Http\Controllers\Admin\FinancesController::class, 'storeBudget'])->name('finances.budget.store');
        Route::post('/operation/store',             [\App\Http\Controllers\Admin\FinancesController::class, 'storeOperation'])->name('finances.operation.store');
        Route::post('/bourse/store',                [\App\Http\Controllers\Admin\FinancesController::class, 'storeBourse'])->name('finances.bourse.store');
        Route::get('/bourse/export',                [\App\Http\Controllers\Admin\FinancesController::class, 'exportBourses'])->name('finances.bourse.export');
        Route::post('/stock/store',                 [\App\Http\Controllers\Admin\FinancesController::class, 'storeStock'])->name('finances.stock.store');
        Route::post('/profile/update',              [\App\Http\Controllers\Admin\FinancesController::class, 'updateProfile'])->name('finances.profile.update');
    });

    // ── تسيير الوسائل والممتلكات (Patrimoine) ──────────────────────────────────
    Route::prefix('dashboard/patrimoine')->group(function () {
        Route::get('/',                             [\App\Http\Controllers\Admin\PatrimoineController::class, 'index'])->name('patrimoine.index');
        Route::post('/equipment/store',             [\App\Http\Controllers\Admin\PatrimoineController::class, 'storeEquipment'])->name('patrimoine.equipment.store');
        Route::post('/vehicule/store',              [\App\Http\Controllers\Admin\PatrimoineController::class, 'storeVehicule'])->name('patrimoine.vehicule.store');
        Route::post('/local/store',                 [\App\Http\Controllers\Admin\PatrimoineController::class, 'storeLocal'])->name('patrimoine.local.store');
        Route::post('/logement/store',              [\App\Http\Controllers\Admin\PatrimoineController::class, 'storeLogement'])->name('patrimoine.logement.store');
        Route::post('/media/update',                [\App\Http\Controllers\Admin\PatrimoineController::class, 'updatePhoto'])->name('patrimoine.media.update');
    });

    // ── الموارد البشرية والإدارية (RH) ─────────────────────────────────────────
    Route::prefix('dashboard/rh-gestion')->group(function () {
        Route::get('/',                             [\App\Http\Controllers\Admin\RHController::class, 'index'])->name('rh-gestion.index');
        Route::post('/personnel/store',             [\App\Http\Controllers\Admin\RHController::class, 'storePersonnel'])->name('rh-gestion.personnel.store');
        Route::post('/formation/store',             [\App\Http\Controllers\Admin\RHController::class, 'storeFormation'])->name('rh-gestion.formation.store');
        Route::post('/activite/store',              [\App\Http\Controllers\Admin\RHController::class, 'storeActivite'])->name('rh-gestion.activite.store');
        Route::post('/competance/store',            [\App\Http\Controllers\Admin\RHController::class, 'storeCompetance'])->name('rh-gestion.competance.store');
        Route::post('/competance/update',           [\App\Http\Controllers\Admin\RHController::class, 'updateCompetance'])->name('rh-gestion.competance.update');
        Route::post('/competance/delete/{id}',      [\App\Http\Controllers\Admin\RHController::class, 'destroyCompetance'])->name('rh-gestion.competance.destroy');
    });

    Route::get('/dashboard/identities',             [\App\Http\Controllers\Admin\IdentityController::class, 'index'])->name('admin.identities.index');
    Route::get('/dashboard/identities/{nin}',       [\App\Http\Controllers\Admin\IdentityController::class, 'show'])->name('admin.identities.show');

    Route::get('/dashboard/preinscrits',            [\App\Http\Controllers\Admin\PreinscritController::class, 'index'])->name('admin.preinscrits.index');
    Route::get('/dashboard/preinscrits/show/{id}',  [\App\Http\Controllers\Admin\PreinscritController::class, 'show'])->name('admin.preinscrits.show');
    Route::post('/dashboard/preinscrits/action',    [\App\Http\Controllers\Admin\PreinscritController::class, 'action'])->name('admin.preinscrits.action');

    // ── المدونة الوطنية للشعب والقطاعات (RNFC) ──────────────────────────────────
    Route::prefix('dashboard/rnfc')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\RnfcController::class, 'index'])->name('admin.rnfc.index');
        
        Route::post('/classification/store', [\App\Http\Controllers\Admin\RnfcController::class, 'storeClassification'])->name('admin.rnfc.classification.store');
        Route::post('/classification/update', [\App\Http\Controllers\Admin\RnfcController::class, 'updateClassification'])->name('admin.rnfc.classification.update');
        Route::post('/classification/delete/{id}', [\App\Http\Controllers\Admin\RnfcController::class, 'deleteClassification'])->name('admin.rnfc.classification.delete');

        Route::post('/secteur/store',        [\App\Http\Controllers\Admin\RnfcController::class, 'storeSecteur'])->name('admin.rnfc.secteur.store');
        Route::post('/secteur/update',       [\App\Http\Controllers\Admin\RnfcController::class, 'updateSecteur'])->name('admin.rnfc.secteur.update');
        Route::post('/secteur/delete/{id}',  [\App\Http\Controllers\Admin\RnfcController::class, 'deleteSecteur'])->name('admin.rnfc.secteur.delete');

        Route::post('/domaine/store',        [\App\Http\Controllers\Admin\RnfcController::class, 'storeDomaine'])->name('admin.rnfc.domaine.store');
        Route::post('/domaine/update',       [\App\Http\Controllers\Admin\RnfcController::class, 'updateDomaine'])->name('admin.rnfc.domaine.update');
        Route::post('/domaine/delete/{id}',  [\App\Http\Controllers\Admin\RnfcController::class, 'deleteDomaine'])->name('admin.rnfc.domaine.delete');

        Route::post('/sousdomaine/store',    [\App\Http\Controllers\Admin\RnfcController::class, 'storeSousdomaine'])->name('admin.rnfc.sousdomaine.store');
        Route::post('/sousdomaine/update',   [\App\Http\Controllers\Admin\RnfcController::class, 'updateSousdomaine'])->name('admin.rnfc.sousdomaine.update');
        Route::post('/sousdomaine/delete/{id}', [\App\Http\Controllers\Admin\RnfcController::class, 'deleteSousdomaine'])->name('admin.rnfc.sousdomaine.delete');
    });

    // ── تفضيلات المستخدم (User Preferences — DB-backed) ──────────────────
    Route::get('/dashboard/preferences',        [\App\Http\Controllers\Admin\PreferencesController::class, 'index'])->name('preferences.index');
    Route::post('/dashboard/preferences/save',  [\App\Http\Controllers\Admin\PreferencesController::class, 'save'])->name('preferences.save');
    Route::post('/dashboard/preferences/reset', [\App\Http\Controllers\Admin\PreferencesController::class, 'reset'])->name('preferences.reset');
    Route::get('/dashboard/notifications/fetch', [DashboardController::class, 'getNotifications'])->name('dashboard.notifications.fetch');
    Route::post('/dashboard/notifications/read', [DashboardController::class, 'markNotificationsAsRead'])->name('dashboard.notifications.read');

    // ── AJAX APIs (sans CSRF — prefixed avec /dashboard/api) ─────────────
    Route::prefix('dashboard/api')->group(function () {
        Route::get('/filter',                  [DashboardController::class, 'filterApi'])->name('dashboard.filter');
        Route::post('/export',                 [DashboardController::class, 'exportRequest'])->name('dashboard.export');
        Route::get('/export/{id}',             [DashboardController::class, 'exportStatus'])->name('dashboard.export.status');
        Route::get('/search-all',              [DashboardController::class, 'searchAll'])->name('dashboard.search_all');
        Route::get('/notifications',           [DashboardController::class, 'getNotifications'])->name('dashboard.notifications');
        Route::post('/notifications/mark-read', [DashboardController::class, 'markNotificationsAsRead'])->name('dashboard.notifications.mark_read');
    });

    // ── Candidates ────────────────────────────────────────────────────────
    Route::get('/dashboard/candidates', [\App\Http\Controllers\Admin\CandidatController::class, 'index'])->name('candidates.index')->middleware('secure.permission:view');
    Route::post('/dashboard/candidates/action', [\App\Http\Controllers\Admin\CandidatController::class, 'action'])->name('candidates.action')->middleware('secure.permission:update');
    Route::post('/dashboard/candidates/store', [\App\Http\Controllers\Admin\CandidatController::class, 'store'])->name('candidates.store')->middleware(['secure.permission:create', 'secure.scope']);
    Route::get('/dashboard/candidates/show/{id}', [\App\Http\Controllers\Admin\CandidatController::class, 'show'])->name('candidates.show')->middleware(['secure.permission:view', 'secure.ownership:App\Models\Candidat,id']);
    Route::post('/dashboard/candidates/update', [\App\Http\Controllers\Admin\CandidatController::class, 'update'])->name('candidates.update')->middleware(['secure.permission:update', 'secure.ownership:App\Models\Candidat,id']);
    Route::post('/dashboard/candidates/delete/{id}', [\App\Http\Controllers\Admin\CandidatController::class, 'destroy'])->name('candidates.delete')->middleware(['secure.permission:delete', 'secure.ownership:App\Models\Candidat,id']);

    // ── Absences ──────────────────────────────────────────────────────────
    Route::prefix('dashboard/absences')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\AbsencesController::class, 'index'])->name('absences.index');
        Route::get('/add',                  [\App\Http\Controllers\Admin\AbsencesController::class, 'add'])->name('absences.add');
        Route::post('/store',               [\App\Http\Controllers\Admin\AbsencesController::class, 'store'])->name('absences.store');
        Route::get('/warnings',             [\App\Http\Controllers\Admin\AbsencesController::class, 'warnings'])->name('absences.warnings');
        Route::get('/print-warning/{id}',   [\App\Http\Controllers\Admin\AbsencesController::class, 'printWarning'])->name('absences.print-warning');
    });

    // ── Grades ────────────────────────────────────────────────────────────
    Route::prefix('dashboard/grades')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\GradesController::class, 'index'])->name('grades.index');
        Route::get('/reconduits',           [\App\Http\Controllers\Admin\GradesController::class, 'reconduitsIndex'])->name('grades.reconduits');
        Route::get('/input',                [\App\Http\Controllers\Admin\GradesController::class, 'input'])->name('grades.input');
        Route::post('/store',               [\App\Http\Controllers\Admin\GradesController::class, 'store'])->name('grades.store');
        Route::get('/transcript/{id}',      [\App\Http\Controllers\Admin\GradesController::class, 'transcript'])->name('grades.transcript');
        Route::get('/deliberation',         [\App\Http\Controllers\Admin\GradesController::class, 'deliberation'])->name('grades.deliberation');
        Route::get('/pv-print',             [\App\Http\Controllers\Admin\GradesController::class, 'pvPrint'])->name('grades.pv-print');
        Route::post('/deliberation/confirm', [\App\Http\Controllers\Admin\GradesController::class, 'confirmDeliberation'])->name('grades.deliberation.confirm');
        Route::get('/semestre-setup',       [\App\Http\Controllers\Admin\GradesController::class, 'semestreSetup'])->name('grades.semestre-setup');
        Route::post('/semestre-setup/save',  [\App\Http\Controllers\Admin\GradesController::class, 'saveSemestreSetup'])->name('grades.semestre-setup.save');
        Route::get('/progress',             [\App\Http\Controllers\Admin\GradesController::class, 'progress'])->name('grades.progress');
        Route::get('/get-employeurs',       [\App\Http\Controllers\Admin\GradesController::class, 'getEmployeurs'])->name('grades.get-employeurs');
        Route::get('/control',              [\App\Http\Controllers\Admin\GradesController::class, 'gradingControl'])->name('grades.control');
        Route::post('/control/save',        [\App\Http\Controllers\Admin\GradesController::class, 'saveGradingControl'])->name('grades.control.save');
        Route::get('/windows',              [\App\Http\Controllers\Admin\GradesController::class, 'windows'])->name('grades.windows');
        Route::post('/windows/store',        [\App\Http\Controllers\Admin\GradesController::class, 'storeWindow'])->name('grades.windows.store');
        Route::post('/windows/delete/{id}',  [\App\Http\Controllers\Admin\GradesController::class, 'deleteWindow'])->name('grades.windows.delete');
    });

    // ── Specialites ───────────────────────────────────────────────────────
    Route::prefix('dashboard/specialites')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Admin\SpecialiteController::class, 'index'])->name('specialites.index');
        Route::get('/cartographie',   [\App\Http\Controllers\Admin\SpecialiteController::class, 'cartographie'])->name('specialites.cartographie');
        Route::get('/print',          [\App\Http\Controllers\Admin\SpecialiteController::class, 'printSpecialites'])->name('specialites.print');
        Route::post('/store',         [\App\Http\Controllers\Admin\SpecialiteController::class, 'storeSpecialite'])->name('specialites.store');
        Route::post('/update',        [\App\Http\Controllers\Admin\SpecialiteController::class, 'updateSpecialite'])->name('specialites.update');
        Route::post('/delete/{id}',   [\App\Http\Controllers\Admin\SpecialiteController::class, 'deleteSpecialite'])->name('specialites.delete');
        Route::post('/import',        [\App\Http\Controllers\Admin\SpecialiteController::class, 'importSpecialites'])->name('specialites.import');
    });


    // ── Formateurs ────────────────────────────────────────────────────────
    Route::get('/dashboard/formateurs', [\App\Http\Controllers\Admin\FormateurController::class, 'index'])->name('formateurs.index');
    Route::get('/dashboard/encadrement', [\App\Http\Controllers\Admin\FormateurController::class, 'index'])->name('encadrement.index');
    Route::get('/dashboard/formateurs/age-distribution', [\App\Http\Controllers\Admin\FormateurController::class, 'ageDistribution'])->name('formateurs.age-distribution');
    Route::get('/dashboard/formateurs/age-distribution/export', [\App\Http\Controllers\Admin\FormateurController::class, 'exportAgeDistribution'])->name('formateurs.age-distribution.export');
    Route::post('/dashboard/formateurs/store', [\App\Http\Controllers\Admin\FormateurController::class, 'store'])->name('formateurs.store');
    Route::get('/dashboard/formateurs/show/{id}', [\App\Http\Controllers\Admin\FormateurController::class, 'show'])->name('formateurs.show');
    Route::post('/dashboard/formateurs/update', [\App\Http\Controllers\Admin\FormateurController::class, 'update'])->name('formateurs.update');
    Route::post('/dashboard/formateurs/delete/{id}', [\App\Http\Controllers\Admin\FormateurController::class, 'destroy'])->name('formateurs.delete');

    // ── Diplomes ──────────────────────────────────────────────────────────
    Route::prefix('dashboard/diplomes')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\DiplomeController::class, 'index'])->name('diplomes.index');
        Route::get('/liste-2021-present',   [\App\Http\Controllers\Admin\DiplomeController::class, 'liste2021'])->name('diplomes.liste2021');
        Route::get('/statistiques',         [\App\Http\Controllers\Admin\DiplomeController::class, 'statistiques'])->name('diplomes.statistiques');
        Route::get('/generate/{id}',        [\App\Http\Controllers\Admin\DiplomeController::class, 'generate'])->name('diplomes.generate');
        Route::get('/print/{id}',           [\App\Http\Controllers\Admin\DiplomeController::class, 'printDiploma'])->name('diplomes.print');
        Route::get('/print-batch',          [\App\Http\Controllers\Admin\DiplomeController::class, 'printBatch'])->name('diplomes.print.batch');
        Route::get('/download-pdf/{id}',    [\App\Http\Controllers\Admin\DiplomeController::class, 'downloadPdf'])->name('diplomes.download.pdf');
        Route::get('/download-pdf-batch',   [\App\Http\Controllers\Admin\DiplomeController::class, 'downloadPdfBatch'])->name('diplomes.download.pdf.batch');
        Route::get('/show/{id}',            [\App\Http\Controllers\Admin\DiplomeController::class, 'show'])->name('diplomes.show');
        Route::post('/update',              [\App\Http\Controllers\Admin\DiplomeController::class, 'update'])->name('diplomes.update');
        Route::post('/delete/{id}',         [\App\Http\Controllers\Admin\DiplomeController::class, 'destroy'])->name('diplomes.delete');
    });

    // ── Apprenants ────────────────────────────────────────────────────────
    Route::prefix('dashboard/apprenants')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\ApprenantController::class, 'index'])->name('apprenants.index')->middleware('secure.permission:view');
        Route::post('/store',               [\App\Http\Controllers\Admin\ApprenantController::class, 'store'])->name('apprenants.store')->middleware(['secure.permission:create', 'secure.scope']);
        Route::get('/show/{id}',            [\App\Http\Controllers\Admin\ApprenantController::class, 'show'])->name('apprenants.show')->middleware(['secure.permission:view', 'secure.ownership:App\Models\Apprenant,id']);
        Route::post('/update',              [\App\Http\Controllers\Admin\ApprenantController::class, 'update'])->name('apprenants.update')->middleware(['secure.permission:update', 'secure.ownership:App\Models\Apprenant,id']);
        Route::post('/delete/{id}',         [\App\Http\Controllers\Admin\ApprenantController::class, 'destroy'])->name('apprenants.delete')->middleware(['secure.permission:delete', 'secure.ownership:App\Models\Apprenant,id']);
    });

    // ── Sections ──────────────────────────────────────────────────────────
    Route::prefix('dashboard/sections')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\SectionController::class, 'index'])->name('sections.index')->middleware('secure.permission:view');
        Route::post('/store',               [\App\Http\Controllers\Admin\SectionController::class, 'store'])->name('sections.store')->middleware(['secure.permission:create', 'secure.scope']);
        Route::get('/show/{id}',            [\App\Http\Controllers\Admin\SectionController::class, 'show'])->name('sections.show')->middleware(['secure.permission:view', 'secure.ownership:App\Models\Section,id']);
        Route::get('/trainees/{id}',        [\App\Http\Controllers\Admin\SectionController::class, 'ajaxGetTrainees'])->name('sections.trainees')->middleware(['secure.permission:view', 'secure.ownership:App\Models\Section,id']);
        Route::post('/validate-trainees/{id}', [\App\Http\Controllers\Admin\SectionController::class, 'bulkValidateTrainees'])->name('sections.validate-trainees')->middleware(['secure.permission:update', 'secure.ownership:App\Models\Section,id']);
        Route::post('/update',              [\App\Http\Controllers\Admin\SectionController::class, 'update'])->name('sections.update')->middleware(['secure.permission:update', 'secure.ownership:App\Models\Section,id']);
        Route::post('/delete/{id}',         [\App\Http\Controllers\Admin\SectionController::class, 'destroy'])->name('sections.delete')->middleware(['secure.permission:delete', 'secure.ownership:App\Models\Section,id']);
    });

    // ── Modules / New Modules ─────────────────────────────────────────────
    Route::get('/dashboard/inscriptions', [\App\Http\Controllers\Admin\ModulesController::class, 'inscriptions'])->name('modules.inscriptions')->middleware('secure.permission:view');
    Route::post('/dashboard/inscriptions/orienter', [\App\Http\Controllers\Admin\ModulesController::class, 'orienterCandidate'])->name('modules.orienter')->middleware('secure.permission:update');
    Route::post('/dashboard/inscriptions/orienter-bulk', [\App\Http\Controllers\Admin\ModulesController::class, 'orienterBulkCandidates'])->name('modules.orienter-bulk')->middleware('secure.permission:update');
    Route::get('/dashboard/inscriptions/ajax/sections-by-offre/{offre_id}', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetSectionsByOffre'])->middleware('secure.permission:view');
    Route::get('/dashboard/integration', [\App\Http\Controllers\Admin\ModulesController::class, 'integration'])->name('modules.integration')->middleware('secure.permission:view');
    Route::post('/dashboard/integration/store', [\App\Http\Controllers\Admin\ModulesController::class, 'storeAgreement'])->name('modules.integration.store')->middleware('secure.permission:create');
    Route::post('/dashboard/integration/delete/{id}', [\App\Http\Controllers\Admin\ModulesController::class, 'deleteAgreement'])->name('modules.integration.delete')->middleware('secure.permission:delete');
    Route::get('/dashboard/sessions', [\App\Http\Controllers\Admin\ModulesController::class, 'sessions'])->name('modules.sessions')->middleware('secure.permission:view');
    Route::post('/dashboard/sessions/store', [\App\Http\Controllers\Admin\ModulesController::class, 'storeSession'])->name('modules.sessions.store')->middleware('secure.permission:create');
    Route::post('/dashboard/sessions/update', [\App\Http\Controllers\Admin\ModulesController::class, 'updateSession'])->name('modules.sessions.update')->middleware('secure.permission:update');
    Route::post('/dashboard/sessions/delete/{id}', [\App\Http\Controllers\Admin\ModulesController::class, 'deleteSession'])->name('modules.sessions.delete')->middleware('secure.permission:delete');
    Route::get('/dashboard/effectifs', [\App\Http\Controllers\Admin\ModulesController::class, 'effectifs'])->name('modules.effectifs')->middleware('secure.permission:view');
    Route::get('/dashboard/reconduits', [\App\Http\Controllers\Admin\ModulesController::class, 'reconduits'])->name('modules.reconduits')->middleware('secure.permission:view');
    Route::get('/dashboard/reconduits/details/{id}', [\App\Http\Controllers\Admin\ModulesController::class, 'reconduitsDetails'])->name('modules.reconduits-details')->middleware('secure.permission:view');
    Route::get('/dashboard/reconduits/edit/{id}', [\App\Http\Controllers\Admin\ModulesController::class, 'editReconduit'])->name('modules.reconduits-edit')->middleware('secure.permission:view');
    Route::post('/dashboard/reconduits/update/{id}', [\App\Http\Controllers\Admin\ModulesController::class, 'updateReconduit'])->name('modules.reconduits-update')->middleware('secure.permission:update');
    
    // ── Trainee Transfer System ──
    Route::post('/dashboard/reconduits/transfer', [\App\Http\Controllers\Admin\ModulesController::class, 'initiateTransfer'])->name('modules.reconduits.transfer')->middleware('secure.permission:create');
    Route::get('/dashboard/reconduits/transfers', [\App\Http\Controllers\Admin\ModulesController::class, 'transfersList'])->name('modules.reconduits.transfers-list')->middleware('secure.permission:view');
    Route::post('/dashboard/reconduits/transfers/action', [\App\Http\Controllers\Admin\ModulesController::class, 'processTransferAction'])->name('modules.reconduits.transfers-action')->middleware('secure.permission:update');
    Route::get('/dashboard/reconduits/ajax/etablissements', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetEtablissements'])->name('modules.reconduits.ajax.etablissements')->middleware('secure.permission:view');
    Route::get('/dashboard/reconduits/ajax/sections', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetSections'])->name('modules.reconduits.ajax.sections')->middleware('secure.permission:view');

    Route::get('/dashboard/discipline', [\App\Http\Controllers\Admin\ModulesController::class, 'discipline'])->name('modules.discipline')->middleware('secure.permission:view');
    Route::post('/dashboard/discipline/store', [\App\Http\Controllers\Admin\ModulesController::class, 'storeDiscipline'])->name('modules.discipline.store')->middleware('secure.permission:create');
    Route::post('/dashboard/discipline/delete/{id}', [\App\Http\Controllers\Admin\ModulesController::class, 'deleteDiscipline'])->name('modules.discipline.delete')->middleware('secure.permission:delete');
    Route::get('/dashboard/distribution-globale', [\App\Http\Controllers\Admin\ModulesController::class, 'distributionGlobale'])->name('modules.distribution-globale')->middleware('secure.permission:view');
    Route::get('/dashboard/distribution-detaillee', [\App\Http\Controllers\Admin\ModulesController::class, 'distributionDetaillee'])->name('modules.distribution-detaillee')->middleware('secure.permission:view');
    Route::get('/dashboard/repas', [\App\Http\Controllers\Admin\ModulesController::class, 'repas'])->name('modules.repas')->middleware('secure.permission:view');
    Route::post('/dashboard/repas/reserver', [\App\Http\Controllers\Admin\ModulesController::class, 'reserverRepas'])->name('modules.repas.reserver')->middleware('secure.permission:create');
    Route::get('/dashboard/documents', [\App\Http\Controllers\Admin\ModulesController::class, 'documents'])->name('modules.documents')->middleware('secure.permission:view');
    Route::get('/dashboard/documents/print/{id}', [\App\Http\Controllers\Admin\ModulesController::class, 'printDocument'])->name('modules.documents.print')->middleware('secure.permission:view');
    Route::post('/dashboard/documents/demander', [\App\Http\Controllers\Admin\ModulesController::class, 'demanderDocument'])->name('modules.documents.demander')->middleware('secure.permission:create');
    Route::get('/dashboard/documents/ajax/modes', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetModes'])->name('modules.documents.ajax.modes')->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/wilayas', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetWilayas'])->name('modules.documents.ajax.wilayas')->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/etablissements', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetEtablissements'])->name('modules.documents.ajax.etablissements')->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/users', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetUsers'])->name('modules.documents.ajax.users')->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/branches', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetBranches'])->name('modules.documents.ajax.branches')->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/specialties', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetSpecialties'])->name('modules.documents.ajax.specialties')->middleware('secure.permission:view');

    // ── Formation ──────────────────────────────────────────────────────────
    Route::prefix('dashboard/formation')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Formation\FormationController::class, 'formation'])->name('formation.index');
        Route::post('/store',               [\App\Http\Controllers\Formation\FormationController::class, 'storeEquipment'])->name('formation.store');
        Route::post('/update',              [\App\Http\Controllers\Formation\FormationController::class, 'updateEquipment'])->name('formation.update');
        Route::post('/delete/{id}',         [\App\Http\Controllers\Formation\FormationController::class, 'deleteEquipment'])->name('formation.delete');
    });

    // ── Evaluation ─────────────────────────────────────────────────────────
    Route::get('/dashboard/evaluation-stagiaires', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'evalStagiaires'])->name('evaluation.stagiaires');
    Route::get('/dashboard/examens', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'examens'])->name('evaluation.examens');
    Route::get('/dashboard/gestion-evaluations', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'gestionEvaluations'])->name('evaluation.gestion');
    Route::post('/dashboard/gestion-evaluations/store', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'storeInspection'])->name('evaluation.gestion.store');
    Route::get('/dashboard/gestion-evaluations/inspecteurs', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'listInspecteurs'])->name('evaluation.inspecteurs');
    Route::get('/dashboard/gestion-evaluations/inspecteurs/details', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'detailsInspecteur'])->name('evaluation.inspecteurs.details');
    Route::get('/dashboard/gestion-evaluations/jury', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'listJuries'])->name('evaluation.jury');
    Route::get('/dashboard/evaluation-finale', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'evalFinale'])->name('evaluation.finale');

    // ── Schedule / Emplois du Temps ────────────────────────────────────────
    Route::prefix('dashboard/schedule')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\ScheduleController::class, 'index'])->name('schedule.index');
        Route::post('/store',               [\App\Http\Controllers\Admin\ScheduleController::class, 'store'])->name('schedule.store');
        Route::post('/update',              [\App\Http\Controllers\Admin\ScheduleController::class, 'update'])->name('schedule.update');
        Route::post('/delete/{id}',         [\App\Http\Controllers\Admin\ScheduleController::class, 'delete'])->name('schedule.delete');
        Route::get('/teacher/{id}',          [\App\Http\Controllers\Admin\ScheduleController::class, 'teacherSchedule'])->name('schedule.teacher');
    });

    // ── User Management ────────────────────────────────────────────────────
    Route::prefix('dashboard/users')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\UtilisateursController::class, 'index'])->name('users.index');
        Route::get('/print',                [\App\Http\Controllers\Admin\UtilisateursController::class, 'printUsers'])->name('users.print');
        Route::post('/store',               [\App\Http\Controllers\Admin\UtilisateursController::class, 'store'])->name('users.store');
        Route::post('/reset-password',      [\App\Http\Controllers\Admin\UtilisateursController::class, 'generatePasswordResetToken'])->name('users.reset-password');
        Route::post('/update',              [\App\Http\Controllers\Admin\UtilisateursController::class, 'update'])->name('users.update');
        Route::post('/delete/{id}',         [\App\Http\Controllers\Admin\UtilisateursController::class, 'destroy'])->name('users.delete');
        Route::post('/generate-api-key',    [\App\Http\Controllers\Admin\UtilisateursController::class, 'generateApiKey'])->name('users.generate-api-key');
        Route::get('/credentials',          [\App\Http\Controllers\Admin\UtilisateursController::class, 'exportCredentials'])->name('users.credentials');
    });

    // ── Settings (Unified) ─────────────────────────────────────────────
    Route::get('/dashboard/settings',         [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/dashboard/settings/update', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/dashboard/settings/backup/download/{filename}', [SettingsController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::get('/dashboard/settings/sovereign/search-targets', [SettingsController::class, 'searchSovereignTargets'])->name('settings.sovereign.search-targets');

    // ── Settings (Takwin Legacy — kept for backward compatibility) ──────
    Route::prefix('dashboard/settings/takwin')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'index'])->name('settings.takwin.index');
        Route::post('/update',              [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'update'])->name('settings.takwin.update');
        Route::post('/sync',                [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'sync'])->name('settings.takwin.sync');
    });
    Route::prefix('dashboard/settings/diplome')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'diplome'])->name('settings.diplome.index');
        Route::post('/update',              [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'updateDiploma'])->name('settings.diplome.update');
    });

    // ── Roles ──────────────────────────────────────────────────────────────
    Route::prefix('dashboard/roles')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\RolesController::class, 'index'])->name('roles.index');
        Route::post('/update',              [\App\Http\Controllers\Admin\RolesController::class, 'update'])->name('roles.update');
    });

    // ── Permissions ────────────────────────────────────────────────────────
    Route::prefix('dashboard/permissions')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\PermissionsController::class, 'index'])->name('permissions.index');
        Route::post('/update',              [\App\Http\Controllers\Admin\PermissionsController::class, 'update'])->name('permissions.update');
    });


    // ── Sync HFSQL ─────────────────────────────────────────────────────────
    Route::prefix('dashboard/sync')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\SyncController::class, 'index'])->name('sync.index');
        Route::post('/enqueue',             [\App\Http\Controllers\Admin\SyncController::class, 'enqueue'])->name('sync.enqueue');
        Route::get('/status',               [\App\Http\Controllers\Admin\SyncController::class, 'status'])->name('sync.status');
        Route::get('/logs',                 [\App\Http\Controllers\Admin\SyncController::class, 'logs'])->name('sync.logs');
        Route::get('/queue',                [\App\Http\Controllers\Admin\SyncController::class, 'queue'])->name('sync.queue');
        Route::post('/retry',               [\App\Http\Controllers\Admin\SyncController::class, 'retry'])->name('sync.retry');
        Route::post('/pause',               [\App\Http\Controllers\Admin\SyncController::class, 'pause'])->name('sync.pause');
        Route::post('/clear',               [\App\Http\Controllers\Admin\SyncController::class, 'clear'])->name('sync.clear');
        Route::get('/compare',              [\App\Http\Controllers\Admin\SyncController::class, 'compare'])->name('sync.compare');
        Route::post('/compare/counts',      [\App\Http\Controllers\Admin\SyncController::class, 'compareCounts'])->name('sync.compare.counts');
    });

    // ── Database Manager ───────────────────────────────────────────────────
    Route::prefix('dashboard/database')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\DatabaseController::class, 'index'])->name('database.index');
        Route::get('/analytics',            [\App\Http\Controllers\Admin\DatabaseController::class, 'analytics'])->name('database.analytics');
        Route::get('/analytics/refresh',    [\App\Http\Controllers\Admin\DatabaseController::class, 'refreshAnalytics'])->name('database.analytics.refresh');
        Route::get('/analytics/explain',    [\App\Http\Controllers\Admin\DatabaseController::class, 'explainTable'])->name('database.analytics.explain');
        Route::get('/describe',             [\App\Http\Controllers\Admin\DatabaseController::class, 'describeTable'])->name('database.describe');
        Route::get('/data',                 [\App\Http\Controllers\Admin\DatabaseController::class, 'getTableData'])->name('database.data');
        Route::post('/query',               [\App\Http\Controllers\Admin\DatabaseController::class, 'executeQuery'])->name('database.query');
        Route::post('/insert',              [\App\Http\Controllers\Admin\DatabaseController::class, 'insertRow'])->name('database.insert');
        Route::post('/update',              [\App\Http\Controllers\Admin\DatabaseController::class, 'updateRow'])->name('database.update');
        Route::post('/delete',              [\App\Http\Controllers\Admin\DatabaseController::class, 'deleteRow'])->name('database.delete');
    });

    // ── Decision Support System (DSS) ──────────────────────────────────────
    Route::prefix('dashboard/dss')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\DecisionSupportController::class, 'index'])->name('dss.index');
        Route::get('/drilldown',            [\App\Http\Controllers\Admin\DecisionSupportController::class, 'drilldown'])->name('dss.drilldown');
    });


    // ── Archive ────────────────────────────────────────────────────────────
    Route::get('/dashboard/archive',    [\App\Http\Controllers\Admin\ArchiveController::class, 'index'])->name('archive.index');

    // ── Notifications ──────────────────────────────────────────────────────
    Route::get('/dashboard/notifications/fetch', [\App\Http\Controllers\Admin\NotificationController::class, 'fetch'])->name('notifications.fetch');
    Route::post('/dashboard/notifications/read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('notifications.read');

    // ── economic partners (apprentissage) ─────────────────────────────────
    Route::get('/dashboard/partenaires', [\App\Http\Controllers\Admin\ApprentissageController::class, 'partenaires'])->name('apprentissage.partenaires');
    Route::post('/dashboard/partenaires/store', [\App\Http\Controllers\Admin\ApprentissageController::class, 'storePartenaire'])->name('apprentissage.partenaires.store');
    Route::post('/dashboard/partenaires/update', [\App\Http\Controllers\Admin\ApprentissageController::class, 'updatePartenaire'])->name('apprentissage.partenaires.update');
    Route::post('/dashboard/partenaires/delete/{id}', [\App\Http\Controllers\Admin\ApprentissageController::class, 'deletePartenaire'])->name('apprentissage.partenaires.delete');
    Route::get('/dashboard/maitres-apprentissage', [\App\Http\Controllers\Admin\ApprentissageController::class, 'maitres'])->name('apprentissage.maitres');
    Route::post('/dashboard/maitres-apprentissage/store', [\App\Http\Controllers\Admin\ApprentissageController::class, 'storeMaitre'])->name('apprentissage.maitres.store');
    Route::post('/dashboard/maitres-apprentissage/update', [\App\Http\Controllers\Admin\ApprentissageController::class, 'updateMaitre'])->name('apprentissage.maitres.update');
    Route::post('/dashboard/maitres-apprentissage/delete/{id}', [\App\Http\Controllers\Admin\ApprentissageController::class, 'deleteMaitre'])->name('apprentissage.maitres.delete');

    // ── Chunked Data Import ───────────────────────────────────────────────
    Route::prefix('dashboard/import')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\ImportController::class, 'index'])->name('import.index');
        Route::get('/tables',               [\App\Http\Controllers\Admin\ImportController::class, 'tables'])->name('import.tables');
        Route::get('/schema',               [\App\Http\Controllers\Admin\ImportController::class, 'schema'])->name('import.schema');
        Route::get('/export',               [\App\Http\Controllers\Admin\ImportController::class, 'export'])->name('import.export');
        Route::post('/upload',              [\App\Http\Controllers\Admin\ImportController::class, 'upload'])->name('import.upload');
        Route::post('/process',             [\App\Http\Controllers\Admin\ImportController::class, 'process'])->name('import.process');
        Route::post('/cleanup',             [\App\Http\Controllers\Admin\ImportController::class, 'cleanup'])->name('import.cleanup');
    });

    // ── HFSQL → MySQL Export / Sync ───────────────────────────────────────
    Route::prefix('dashboard/hfsql-export')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Admin\HfsqlExportController::class, 'index'])->name('hfsql.export.index');
        Route::get('/tables',        [\App\Http\Controllers\Admin\HfsqlExportController::class, 'tables'])->name('hfsql.export.tables');
        Route::get('/count',         [\App\Http\Controllers\Admin\HfsqlExportController::class, 'count'])->name('hfsql.export.count');
        Route::post('/bulk-counts',  [\App\Http\Controllers\Admin\HfsqlExportController::class, 'bulkCounts'])->name('hfsql.export.bulk_counts');
        Route::get('/stream',        [\App\Http\Controllers\Admin\HfsqlExportController::class, 'stream'])->name('hfsql.export.stream');
        Route::get('/download',      [\App\Http\Controllers\Admin\HfsqlExportController::class, 'download'])->name('hfsql.export.download');
        Route::post('/sync-to-mysql',[\App\Http\Controllers\Admin\HfsqlExportController::class, 'syncToMysql'])->name('hfsql.export.sync');
    });

    // ── Reporting & Export ─────────────────────────────────────────────────
    Route::get('/dashboard/export/{type}', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('reporting.export');
    Route::get('/dashboard/print/{type}', [\App\Http\Controllers\Admin\ReportController::class, 'printReport'])->name('reporting.print');
    Route::get('/dashboard/pdf/{type}', [\App\Http\Controllers\Admin\ReportController::class, 'pdfReport'])->name('reporting.pdf');

    // ── Employee Space ─────────────────────────────────────────────────────
    Route::get('/dashboard/espace-employe',          [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'index'])->name('espace-employe.index');
    Route::get('/dashboard/espace-employe/get/{id}', [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'getEmployee'])->name('espace-employe.get');
    Route::post('/dashboard/espace-employe/update/{id}', [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'updateEmployee'])->name('espace-employe.update');

    // ── Digital Cards Center ────────────────────────────────────────────────
    Route::get('/dashboard/digital-cards',             [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'digitalCards'])->name('digital-cards.index');
    Route::get('/dashboard/digital-cards/trainee/{id}', [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'getTrainee'])->name('digital-cards.trainee');

    // ── Dashboard Builder ──────────────────────────────────────────────────
    Route::get('/dashboard/builder',                  [\App\Http\Controllers\Admin\DashboardBuilderController::class, 'index'])->name('builder.index');
    Route::get('/dashboard/builder/config/{userId}/{portalNum}', [\App\Http\Controllers\Admin\DashboardBuilderController::class, 'getPortalConfig'])->name('builder.config');
    Route::post('/dashboard/builder/save',             [\App\Http\Controllers\Admin\DashboardBuilderController::class, 'savePortalConfig'])->name('builder.save');

    // ── Audit Logs ─────────────────────────────────────────────────────────
    Route::get('/dashboard/audit-logs',              [\App\Http\Controllers\Admin\AuditController::class, 'index'])->name('audit.index');
    Route::get('/dashboard/audit-logs/export',       [\App\Http\Controllers\Admin\AuditController::class, 'export'])->name('audit.export');

    // ── Security Center Dashboard (Admin Only) ─────────────────────────────
    Route::get('/dashboard/security', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'index'])->name('security.index');
    Route::get('/dashboard/security/logs', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'logs'])->name('security.logs');
    Route::get('/dashboard/security/logs/export', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'export'])->name('security.logs.export');
    Route::get('/dashboard/security/mfa', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'mfaManagement'])->name('security.mfa.management');
    Route::post('/dashboard/security/mfa/global', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'updateGlobalMfa'])->name('security.mfa.global.update');
    Route::post('/dashboard/security/mfa/user/toggle', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'toggleUserMfa'])->name('security.mfa.user.toggle');
    Route::post('/dashboard/security/mfa/user/reset', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'resetUserMfa'])->name('security.mfa.user.reset');
    Route::post('/dashboard/security/ip-ban/toggle', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'toggleIpBanning'])->name('security.ip-ban.toggle');

    // ── MFA Setup & Verification Routes ────────────────────────────────────
    Route::get('/security/mfa', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'index'])->name('security.mfa.index');
    Route::get('/security/mfa/setup', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'setup'])->name('security.mfa.setup');
    Route::post('/security/mfa/setup/confirm', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'confirmSetup'])->name('security.mfa.setup.confirm');
    Route::get('/security/mfa/verify', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'showVerifyForm'])->name('security.mfa.verify');
    Route::post('/security/mfa/verify', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'verify'])->name('security.mfa.verify.post');
    Route::post('/security/mfa/disable', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'disable'])->name('security.mfa.disable');
    Route::post('/security/mfa/recovery-codes/regenerate', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('security.mfa.recovery-codes.regenerate');
    Route::post('/security/mfa/device/revoke/{id}', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'revokeDevice'])->name('security.mfa.device.revoke');
    Route::get('/security/mfa/recovery-codes', function() {
        return view('admin.security.mfa.recovery-codes');
    })->name('security.mfa.recovery-codes');


    // ── Digital API Credentials / API Center ────────────────────────────────────
    Route::get('/dashboard/api-credentials',         [\App\Http\Controllers\Admin\ApiCenterController::class, 'index'])->name('api-credentials.index');
    Route::get('/dashboard/api-center',              [\App\Http\Controllers\Admin\ApiCenterController::class, 'index'])->name('api-center.index');
    Route::post('/dashboard/api-center/store',        [\App\Http\Controllers\Admin\ApiCenterController::class, 'store'])->name('api-center.store');
    Route::post('/dashboard/api-center/update/{id}',  [\App\Http\Controllers\Admin\ApiCenterController::class, 'update'])->name('api-center.update');
    Route::post('/dashboard/api-center/toggle/{id}',  [\App\Http\Controllers\Admin\ApiCenterController::class, 'toggle'])->name('api-center.toggle');
    Route::post('/dashboard/api-center/delete/{id}',  [\App\Http\Controllers\Admin\ApiCenterController::class, 'destroy'])->name('api-center.delete');

    // ── Teacher/Employee Custom Dashboard Actions ──────────────────────────
    Route::post('/dashboard/formateur/grades/save', [DashboardController::class, 'saveTeacherGrades']);
    Route::post('/dashboard/formateur/attendance/save', [DashboardController::class, 'saveTeacherAttendance']);
    Route::post('/dashboard/employee/profile/update', [DashboardController::class, 'updateEmployeeProfile']);
    Route::post('/dashboard/employee/leaves/store', [DashboardController::class, 'storeLeaveRequest']);
    Route::post('/dashboard/employee/documents/request', [DashboardController::class, 'requestEmployeeDocument']);
    Route::post('/dashboard/employee/messages/send', [DashboardController::class, 'sendEmployeeMessage']);

    }); // Closing activation.check
}); // Closing check.session

// ═══════════════════════════════════════════════════════════════════════════
// §2b  /sig/ PREFIX MIRROR — Pour accès via XAMPP subfolder & port 8000
// Toutes les routes authentifiées sont dupliquées sous le préfixe /sig/
// ═══════════════════════════════════════════════════════════════════════════

Route::prefix('sig')->middleware('check.session')->group(function () {
    Route::get('/session/check-active', [\App\Http\Controllers\Auth\LoginController::class, 'checkActiveSession'])->name('session.check-active.sig');

    // ── Licensing Shield Page (accessible when logged in, but not activated) ──
    Route::get('/activate', [\App\Http\Controllers\Auth\ActivationController::class, 'showShield'])->name('activation.shield.sig');
    Route::post('/activate', [\App\Http\Controllers\Auth\ActivationController::class, 'activate']);

    Route::middleware(['activation.check', 'mfa.check'])->group(function () {

        // ── Profil ────────────────────────────────────────────────────────────
        Route::get('/dashboard/profile',         [ProfileController::class, 'index']);
    Route::post('/dashboard/profile/update', [ProfileController::class, 'update']);

    // ── Etablissement Profile ─────────────────────────────────────────────
    Route::get('/dashboard/etablissement',         [\App\Http\Controllers\Admin\EtablissementController::class, 'show'])->name('etablissement.show.sig');
    Route::post('/dashboard/etablissement/update', [\App\Http\Controllers\Admin\EtablissementController::class, 'update'])->name('etablissement.update.sig');

    // ── Dashboard principal ───────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/stats/api', [DashboardController::class, 'statsApi']);
    Route::post('/dashboard/stats/refresh', [DashboardController::class, 'statsRefresh']);

    // ── Department dashboards ─────────────────────────────────────────────
    Route::get('/dashboard/finance', [DashboardController::class, 'viewFinance']);
    Route::get('/dashboard/rh',      [DashboardController::class, 'viewRh']);
    Route::get('/dashboard/plan',    [DashboardController::class, 'viewPlan']);
    Route::get('/dashboard/coop',    [DashboardController::class, 'viewCoop']);
    Route::get('/dashboard/it',      [DashboardController::class, 'viewIt']);
    Route::get('/dashboard/exam',    [DashboardController::class, 'viewExam']);
    Route::get('/dashboard/trak',    [DashboardController::class, 'viewTrak']);
    Route::get('/dashboard/org',     [DashboardController::class, 'viewTrak']);
    Route::get('/dashboard/edu',     [DashboardController::class, 'viewEdu']);
    Route::get('/dashboard/dfcri',   [DashboardController::class, 'viewDfcri']);

    // ── Central Dashboards Database Actions ────────────────────────────────
    Route::post('/dashboard/finance/add-budget', [DashboardController::class, 'addBudget']);
    Route::post('/dashboard/finance/update-budget', [DashboardController::class, 'updateBudget']);
    Route::post('/dashboard/finance/delete-budget/{id}', [DashboardController::class, 'deleteBudget']);

    Route::post('/dashboard/finance/add-operation', [DashboardController::class, 'addOperation']);
    Route::post('/dashboard/finance/update-operation', [DashboardController::class, 'updateOperation']);
    Route::post('/dashboard/finance/delete-operation/{id}', [DashboardController::class, 'deleteOperation']);

    Route::post('/dashboard/finance/send-notification', [DashboardController::class, 'sendFinanceNotification']);
    Route::post('/dashboard/it/add-api-key',      [DashboardController::class, 'addApiKey']);
    Route::post('/dashboard/plan/add-project',   [DashboardController::class, 'addProject']);
    Route::post('/dashboard/coop/add-agreement', [DashboardController::class, 'addAgreement']);
    Route::post('/dashboard/dfcri/add-partner',  [DashboardController::class, 'addPartner']);
    Route::post('/dashboard/edu/add-specialty',  [DashboardController::class, 'addSpecialty']);
    Route::post('/dashboard/exam/add-session',   [DashboardController::class, 'addSession']);
    Route::post('/dashboard/trak/add-course',    [DashboardController::class, 'addCourse']);
    Route::post('/dashboard/rh/add-employee',    [DashboardController::class, 'addEmployee']);
    Route::post('/dashboard/central/add-study',  [DashboardController::class, 'addStudy']);

    // ── Offres ────────────────────────────────────────────────────────────
    Route::prefix('dashboard/offres')->group(function () {
        Route::get('/',                   [OffresController::class, 'index']);
        Route::get('/validation',         [OffresController::class, 'validation']);
        Route::get('/print',              [OffresController::class, 'printOffres']);
        Route::post('/store',             [OffresController::class, 'storeOffre']);
        Route::post('/update',            [OffresController::class, 'updateOffre']);
        Route::post('/delete/{id}',       [OffresController::class, 'deleteOffre']);
        Route::post('/soumettre',         [OffresController::class, 'soumettreOffre']);
        Route::post('/valider-direction', [OffresController::class, 'validerDirection']);
        Route::post('/valider-centrale',  [OffresController::class, 'validerCentrale']);
    });

    // ── /sig mirror: التسيير المالي ───────────────────────────────────────
    Route::prefix('dashboard/finances')->group(function () {
        Route::get('/',                             [\App\Http\Controllers\Admin\FinancesController::class, 'index']);
        Route::post('/grades/store',                [\App\Http\Controllers\Admin\FinancesController::class, 'storeGrade']);
        Route::post('/grades/update',               [\App\Http\Controllers\Admin\FinancesController::class, 'updateGrade']);
        Route::post('/grades/delete/{id}',          [\App\Http\Controllers\Admin\FinancesController::class, 'deleteGrade']);
        Route::get('/grades/print',                 [\App\Http\Controllers\Admin\FinancesController::class, 'printGrades']);
        Route::post('/programmes/store',            [\App\Http\Controllers\Admin\FinancesController::class, 'storeProgramme']);
        Route::post('/programmes/update',           [\App\Http\Controllers\Admin\FinancesController::class, 'updateProgramme']);
        Route::post('/programmes/delete/{id}',      [\App\Http\Controllers\Admin\FinancesController::class, 'deleteProgramme']);
        Route::get('/programmes/print',             [\App\Http\Controllers\Admin\FinancesController::class, 'printProgrammes']);
        Route::post('/sous-programmes/store',       [\App\Http\Controllers\Admin\FinancesController::class, 'storeSousProgramme']);
        Route::post('/sous-programmes/update',      [\App\Http\Controllers\Admin\FinancesController::class, 'updateSousProgramme']);
        Route::post('/sous-programmes/delete/{id}', [\App\Http\Controllers\Admin\FinancesController::class, 'deleteSousProgramme']);
        Route::post('/fournisseurs/store',          [\App\Http\Controllers\Admin\FinancesController::class, 'storeFournisseur']);
        Route::post('/fournisseurs/update',         [\App\Http\Controllers\Admin\FinancesController::class, 'updateFournisseur']);
        Route::post('/fournisseurs/delete/{id}',    [\App\Http\Controllers\Admin\FinancesController::class, 'deleteFournisseur']);
        // Extended
        Route::post('/budget/store',                [\App\Http\Controllers\Admin\FinancesController::class, 'storeBudget']);
        Route::post('/operation/store',             [\App\Http\Controllers\Admin\FinancesController::class, 'storeOperation']);
        Route::post('/bourse/store',                [\App\Http\Controllers\Admin\FinancesController::class, 'storeBourse']);
        Route::get('/bourse/export',                [\App\Http\Controllers\Admin\FinancesController::class, 'exportBourses']);
        Route::post('/stock/store',                 [\App\Http\Controllers\Admin\FinancesController::class, 'storeStock']);
        Route::post('/profile/update',              [\App\Http\Controllers\Admin\FinancesController::class, 'updateProfile']);
    });

    // ── /sig mirror: تسيير الوسائل والممتلكات (Patrimoine) ─────────────────────
    Route::prefix('dashboard/patrimoine')->group(function () {
        Route::get('/',                             [\App\Http\Controllers\Admin\PatrimoineController::class, 'index']);
        Route::post('/equipment/store',             [\App\Http\Controllers\Admin\PatrimoineController::class, 'storeEquipment']);
        Route::post('/vehicule/store',              [\App\Http\Controllers\Admin\PatrimoineController::class, 'storeVehicule']);
        Route::post('/local/store',                 [\App\Http\Controllers\Admin\PatrimoineController::class, 'storeLocal']);
        Route::post('/logement/store',              [\App\Http\Controllers\Admin\PatrimoineController::class, 'storeLogement']);
        Route::post('/media/update',                [\App\Http\Controllers\Admin\PatrimoineController::class, 'updatePhoto']);
    });

    // ── /sig mirror: الموارد البشرية والإدارية (RH) ─────────────────────────
    Route::prefix('dashboard/rh-gestion')->group(function () {
        Route::get('/',                             [\App\Http\Controllers\Admin\RHController::class, 'index']);
        Route::post('/personnel/store',             [\App\Http\Controllers\Admin\RHController::class, 'storePersonnel']);
        Route::post('/formation/store',             [\App\Http\Controllers\Admin\RHController::class, 'storeFormation']);
        Route::post('/activite/store',              [\App\Http\Controllers\Admin\RHController::class, 'storeActivite']);
    });

    // ── /sig mirror: تفضيلات المستخدم ────────────────────────────────────
    Route::get('/dashboard/preferences',        [\App\Http\Controllers\Admin\PreferencesController::class, 'index']);
    Route::post('/dashboard/preferences/save',  [\App\Http\Controllers\Admin\PreferencesController::class, 'save']);
    Route::post('/dashboard/preferences/reset', [\App\Http\Controllers\Admin\PreferencesController::class, 'reset']);
    Route::get('/dashboard/notifications/fetch', [DashboardController::class, 'getNotifications']);
    Route::post('/dashboard/notifications/read', [DashboardController::class, 'markNotificationsAsRead']);

    // ── AJAX APIs ─────────────────────────────────────────────────────────
    Route::prefix('dashboard/api')->group(function () {
        Route::get('/filter',                  [DashboardController::class, 'filterApi']);
        Route::post('/export',                 [DashboardController::class, 'exportRequest']);
        Route::get('/export/{id}',             [DashboardController::class, 'exportStatus']);
        Route::get('/search-all',              [DashboardController::class, 'searchAll']);
        Route::get('/notifications',           [DashboardController::class, 'getNotifications']);
        Route::post('/notifications/mark-read', [DashboardController::class, 'markNotificationsAsRead']);
    });

    // ── Candidates ────────────────────────────────────────────────────────
    Route::get('/dashboard/candidates',        [\App\Http\Controllers\Admin\CandidatController::class, 'index'])->middleware('secure.permission:view');
    Route::post('/dashboard/candidates/action',[\App\Http\Controllers\Admin\CandidatController::class, 'action'])->middleware('secure.permission:update');
    Route::post('/dashboard/candidates/store', [\App\Http\Controllers\Admin\CandidatController::class, 'store'])->middleware(['secure.permission:create', 'secure.scope']);
    Route::get('/dashboard/candidates/show/{id}', [\App\Http\Controllers\Admin\CandidatController::class, 'show'])->middleware(['secure.permission:view', 'secure.ownership:App\Models\Candidat,id']);
    Route::post('/dashboard/candidates/update', [\App\Http\Controllers\Admin\CandidatController::class, 'update'])->middleware(['secure.permission:update', 'secure.ownership:App\Models\Candidat,id']);
    Route::post('/dashboard/candidates/delete/{id}', [\App\Http\Controllers\Admin\CandidatController::class, 'destroy'])->middleware(['secure.permission:delete', 'secure.ownership:App\Models\Candidat,id']);

    // ── Absences ──────────────────────────────────────────────────────────
    Route::prefix('dashboard/absences')->group(function () {
        Route::get('/',                  [\App\Http\Controllers\Admin\AbsencesController::class, 'index']);
        Route::get('/add',               [\App\Http\Controllers\Admin\AbsencesController::class, 'add']);
        Route::post('/store',            [\App\Http\Controllers\Admin\AbsencesController::class, 'store']);
        Route::get('/warnings',          [\App\Http\Controllers\Admin\AbsencesController::class, 'warnings']);
        Route::get('/print-warning/{id}',[\App\Http\Controllers\Admin\AbsencesController::class, 'printWarning']);
    });

    // ── Grades ────────────────────────────────────────────────────────────
    Route::prefix('dashboard/grades')->group(function () {
        Route::get('/',                [\App\Http\Controllers\Admin\GradesController::class, 'index']);
        Route::get('/reconduits',      [\App\Http\Controllers\Admin\GradesController::class, 'reconduitsIndex']);
        Route::get('/input',           [\App\Http\Controllers\Admin\GradesController::class, 'input']);
        Route::post('/store',          [\App\Http\Controllers\Admin\GradesController::class, 'store']);
        Route::get('/transcript/{id}', [\App\Http\Controllers\Admin\GradesController::class, 'transcript']);
        Route::get('/deliberation',    [\App\Http\Controllers\Admin\GradesController::class, 'deliberation']);
        Route::get('/pv-print',        [\App\Http\Controllers\Admin\GradesController::class, 'pvPrint']);
        Route::get('/progress',        [\App\Http\Controllers\Admin\GradesController::class, 'progress']);
        Route::get('/get-employeurs',  [\App\Http\Controllers\Admin\GradesController::class, 'getEmployeurs']);
        Route::get('/control',         [\App\Http\Controllers\Admin\GradesController::class, 'gradingControl']);
        Route::post('/control/save',   [\App\Http\Controllers\Admin\GradesController::class, 'saveGradingControl']);
    });

    // ── Modules / Integration ─────────────────────────────────────────────
    Route::get('/dashboard/inscriptions',              [\App\Http\Controllers\Admin\ModulesController::class, 'inscriptions'])->middleware('secure.permission:view');
    Route::post('/dashboard/inscriptions/orienter',    [\App\Http\Controllers\Admin\ModulesController::class, 'orienterCandidate'])->middleware('secure.permission:update');
    Route::get('/dashboard/integration',               [\App\Http\Controllers\Admin\ModulesController::class, 'integration'])->middleware('secure.permission:view');
    Route::post('/dashboard/integration/store',        [\App\Http\Controllers\Admin\ModulesController::class, 'storeAgreement'])->middleware('secure.permission:create');
    Route::post('/dashboard/integration/delete/{id}',  [\App\Http\Controllers\Admin\ModulesController::class, 'deleteAgreement'])->middleware('secure.permission:delete');
    Route::get('/dashboard/sessions',                  [\App\Http\Controllers\Admin\ModulesController::class, 'sessions'])->middleware('secure.permission:view');
    Route::post('/dashboard/sessions/store',           [\App\Http\Controllers\Admin\ModulesController::class, 'storeSession'])->middleware('secure.permission:create');
    Route::post('/dashboard/sessions/update',          [\App\Http\Controllers\Admin\ModulesController::class, 'updateSession'])->middleware('secure.permission:update');
    Route::post('/dashboard/sessions/delete/{id}',     [\App\Http\Controllers\Admin\ModulesController::class, 'deleteSession'])->middleware('secure.permission:delete');
    Route::get('/dashboard/effectifs',                 [\App\Http\Controllers\Admin\ModulesController::class, 'effectifs'])->middleware('secure.permission:view');
    Route::get('/dashboard/reconduits',                [\App\Http\Controllers\Admin\ModulesController::class, 'reconduits'])->middleware('secure.permission:view');
    Route::get('/dashboard/reconduits/details/{id}',   [\App\Http\Controllers\Admin\ModulesController::class, 'reconduitsDetails'])->middleware('secure.permission:view');
    Route::get('/dashboard/reconduits/edit/{id}',      [\App\Http\Controllers\Admin\ModulesController::class, 'editReconduit'])->middleware('secure.permission:view');
    Route::post('/dashboard/reconduits/update/{id}',   [\App\Http\Controllers\Admin\ModulesController::class, 'updateReconduit'])->middleware('secure.permission:update');
    Route::get('/dashboard/discipline',                [\App\Http\Controllers\Admin\ModulesController::class, 'discipline'])->middleware('secure.permission:view');
    Route::post('/dashboard/discipline/store',         [\App\Http\Controllers\Admin\ModulesController::class, 'storeDiscipline'])->middleware('secure.permission:create');
    Route::post('/dashboard/discipline/delete/{id}',   [\App\Http\Controllers\Admin\ModulesController::class, 'deleteDiscipline'])->middleware('secure.permission:delete');
    Route::get('/dashboard/distribution-globale',      [\App\Http\Controllers\Admin\ModulesController::class, 'distributionGlobale'])->middleware('secure.permission:view');
    Route::get('/dashboard/distribution-detaillee',    [\App\Http\Controllers\Admin\ModulesController::class, 'distributionDetaillee'])->middleware('secure.permission:view');
    Route::get('/dashboard/repas',                     [\App\Http\Controllers\Admin\ModulesController::class, 'repas'])->middleware('secure.permission:view');
    Route::post('/dashboard/repas/reserver',           [\App\Http\Controllers\Admin\ModulesController::class, 'reserverRepas'])->middleware('secure.permission:create');
    Route::get('/dashboard/documents',                 [\App\Http\Controllers\Admin\ModulesController::class, 'documents'])->middleware('secure.permission:view');
    Route::get('/dashboard/documents/print/{id}',      [\App\Http\Controllers\Admin\ModulesController::class, 'printDocument'])->middleware('secure.permission:view');
    Route::post('/dashboard/documents/demander',       [\App\Http\Controllers\Admin\ModulesController::class, 'demanderDocument'])->middleware('secure.permission:create');
    Route::get('/dashboard/documents/ajax/wilayas',    [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetWilayas'])->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/modes',      [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetModes'])->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/etablissements', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetEtablissements'])->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/users',       [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetUsers'])->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/branches',    [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetBranches'])->middleware('secure.permission:view');
    Route::get('/dashboard/documents/ajax/specialties', [\App\Http\Controllers\Admin\ModulesController::class, 'ajaxGetSpecialties'])->middleware('secure.permission:view');

    // ── Formation ─────────────────────────────────────────────────────────
    Route::prefix('dashboard/formation')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Formation\FormationController::class, 'formation']);
        Route::post('/store',       [\App\Http\Controllers\Formation\FormationController::class, 'storeEquipment']);
        Route::post('/update',      [\App\Http\Controllers\Formation\FormationController::class, 'updateEquipment']);
        Route::post('/delete/{id}', [\App\Http\Controllers\Formation\FormationController::class, 'deleteEquipment']);
    });

    // ── Evaluation ────────────────────────────────────────────────────────
    Route::get('/dashboard/evaluation-stagiaires', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'evalStagiaires']);
    Route::get('/dashboard/examens',               [\App\Http\Controllers\Evaluation\EvaluationController::class, 'examens']);
    Route::get('/dashboard/gestion-evaluations',   [\App\Http\Controllers\Evaluation\EvaluationController::class, 'gestionEvaluations']);
    Route::get('/dashboard/evaluation-finale',     [\App\Http\Controllers\Evaluation\EvaluationController::class, 'evalFinale']);

    // ── Schedule ─────────────────────────────────────────────────────────
    Route::prefix('dashboard/schedule')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Admin\ScheduleController::class, 'index']);
        Route::post('/store',       [\App\Http\Controllers\Admin\ScheduleController::class, 'store']);
        Route::post('/update',      [\App\Http\Controllers\Admin\ScheduleController::class, 'update']);
        Route::post('/delete/{id}', [\App\Http\Controllers\Admin\ScheduleController::class, 'delete']);
    });

    // ── User Management ───────────────────────────────────────────────────
    Route::prefix('dashboard/users')->group(function () {
        Route::get('/',                  [\App\Http\Controllers\Admin\UtilisateursController::class, 'index']);
        Route::get('/print',             [\App\Http\Controllers\Admin\UtilisateursController::class, 'printUsers']);
        Route::post('/store',            [\App\Http\Controllers\Admin\UtilisateursController::class, 'store']);
        Route::post('/reset-password',   [\App\Http\Controllers\Admin\UtilisateursController::class, 'generatePasswordResetToken']);
        Route::post('/update',           [\App\Http\Controllers\Admin\UtilisateursController::class, 'update']);
        Route::post('/delete/{id}',      [\App\Http\Controllers\Admin\UtilisateursController::class, 'destroy']);
        Route::post('/generate-api-key', [\App\Http\Controllers\Admin\UtilisateursController::class, 'generateApiKey']);
        Route::get('/credentials',       [\App\Http\Controllers\Admin\UtilisateursController::class, 'exportCredentials']);
    });

    // ── Settings ─────────────────────────────────────────────────────────
    Route::get('/dashboard/settings',         [SettingsController::class, 'index']);
    Route::post('/dashboard/settings/update', [SettingsController::class, 'update']);
    Route::get('/dashboard/settings/backup/download/{filename}', [SettingsController::class, 'downloadBackup']);
    Route::get('/dashboard/settings/sovereign/search-targets', [SettingsController::class, 'searchSovereignTargets']);

    Route::prefix('dashboard/settings/takwin')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'index']);
        Route::post('/update',        [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'update']);
        Route::post('/sync',          [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'sync']);
    });
    Route::prefix('dashboard/settings/diplome')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'diplome']);
        Route::post('/update',        [\App\Http\Controllers\Admin\TakwinSettingsController::class, 'updateDiploma']);
    });

    // ── Roles & Permissions ───────────────────────────────────────────────
    Route::prefix('dashboard/roles')->group(function () {
        Route::get('/',         [\App\Http\Controllers\Admin\RolesController::class, 'index']);
        Route::post('/update',  [\App\Http\Controllers\Admin\RolesController::class, 'update']);
    });
    Route::prefix('dashboard/permissions')->group(function () {
        Route::get('/',        [\App\Http\Controllers\Admin\PermissionsController::class, 'index']);
        Route::post('/update', [\App\Http\Controllers\Admin\PermissionsController::class, 'update']);
    });

    // ── Sync HFSQL ────────────────────────────────────────────────────────
    Route::prefix('dashboard/sync')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Admin\SyncController::class, 'index']);
        Route::post('/enqueue',       [\App\Http\Controllers\Admin\SyncController::class, 'enqueue']);
        Route::get('/status',         [\App\Http\Controllers\Admin\SyncController::class, 'status']);
        Route::get('/logs',           [\App\Http\Controllers\Admin\SyncController::class, 'logs']);
        Route::get('/queue',          [\App\Http\Controllers\Admin\SyncController::class, 'queue']);
        Route::post('/retry',         [\App\Http\Controllers\Admin\SyncController::class, 'retry']);
        Route::post('/pause',         [\App\Http\Controllers\Admin\SyncController::class, 'pause']);
        Route::post('/clear',         [\App\Http\Controllers\Admin\SyncController::class, 'clear']);
        Route::get('/compare',        [\App\Http\Controllers\Admin\SyncController::class, 'compare']);
        Route::post('/compare/counts',[\App\Http\Controllers\Admin\SyncController::class, 'compareCounts']);
    });

    Route::prefix('dashboard/database')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\DatabaseController::class, 'index']);
        Route::get('/analytics',            [\App\Http\Controllers\Admin\DatabaseController::class, 'analytics']);
        Route::get('/analytics/refresh',    [\App\Http\Controllers\Admin\DatabaseController::class, 'refreshAnalytics']);
        Route::get('/analytics/explain',    [\App\Http\Controllers\Admin\DatabaseController::class, 'explainTable']);
        Route::get('/describe',             [\App\Http\Controllers\Admin\DatabaseController::class, 'describeTable']);
        Route::get('/data',                 [\App\Http\Controllers\Admin\DatabaseController::class, 'getTableData']);
        Route::post('/query',               [\App\Http\Controllers\Admin\DatabaseController::class, 'executeQuery']);
        Route::post('/insert',              [\App\Http\Controllers\Admin\DatabaseController::class, 'insertRow']);
        Route::post('/update',              [\App\Http\Controllers\Admin\DatabaseController::class, 'updateRow']);
        Route::post('/delete',              [\App\Http\Controllers\Admin\DatabaseController::class, 'deleteRow']);
    });

    // ── Decision Support System (DSS) ──────────────────────────────────────
    Route::prefix('dashboard/dss')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\DecisionSupportController::class, 'index']);
        Route::get('/drilldown',            [\App\Http\Controllers\Admin\DecisionSupportController::class, 'drilldown']);
    });


    // ── Archive ───────────────────────────────────────────────────────────
    Route::get('/dashboard/archive', [\App\Http\Controllers\Admin\ArchiveController::class, 'index']);

    // ── Notifications ─────────────────────────────────────────────────────
    Route::get('/dashboard/notifications/fetch',  [\App\Http\Controllers\Admin\NotificationController::class, 'fetch']);
    Route::post('/dashboard/notifications/read',  [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead']);

    // ── Apprentissage ─────────────────────────────────────────────────────
    Route::get('/dashboard/partenaires',                        [\App\Http\Controllers\Admin\ApprentissageController::class, 'partenaires']);
    Route::post('/dashboard/partenaires/store',                 [\App\Http\Controllers\Admin\ApprentissageController::class, 'storePartenaire']);
    Route::post('/dashboard/partenaires/update',                [\App\Http\Controllers\Admin\ApprentissageController::class, 'updatePartenaire']);
    Route::post('/dashboard/partenaires/delete/{id}',           [\App\Http\Controllers\Admin\ApprentissageController::class, 'deletePartenaire']);
    Route::get('/dashboard/maitres-apprentissage',              [\App\Http\Controllers\Admin\ApprentissageController::class, 'maitres']);
    Route::post('/dashboard/maitres-apprentissage/store',       [\App\Http\Controllers\Admin\ApprentissageController::class, 'storeMaitre']);
    Route::post('/dashboard/maitres-apprentissage/update',      [\App\Http\Controllers\Admin\ApprentissageController::class, 'updateMaitre']);
    Route::post('/dashboard/maitres-apprentissage/delete/{id}', [\App\Http\Controllers\Admin\ApprentissageController::class, 'deleteMaitre']);

    // ── Import ────────────────────────────────────────────────────────────
    Route::prefix('dashboard/import')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Admin\ImportController::class, 'index']);
        Route::get('/tables',     [\App\Http\Controllers\Admin\ImportController::class, 'tables']);
        Route::get('/schema',     [\App\Http\Controllers\Admin\ImportController::class, 'schema']);
        Route::get('/export',     [\App\Http\Controllers\Admin\ImportController::class, 'export']);
        Route::post('/upload',    [\App\Http\Controllers\Admin\ImportController::class, 'upload']);
        Route::post('/process',   [\App\Http\Controllers\Admin\ImportController::class, 'process']);
        Route::post('/cleanup',   [\App\Http\Controllers\Admin\ImportController::class, 'cleanup']);
    });

    // ── HFSQL Export / Sync ───────────────────────────────────────────────
    Route::prefix('dashboard/hfsql-export')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Admin\HfsqlExportController::class, 'index']);
        Route::get('/tables',         [\App\Http\Controllers\Admin\HfsqlExportController::class, 'tables']);
        Route::get('/count',          [\App\Http\Controllers\Admin\HfsqlExportController::class, 'count']);
        Route::post('/bulk-counts',   [\App\Http\Controllers\Admin\HfsqlExportController::class, 'bulkCounts']);
        Route::get('/stream',         [\App\Http\Controllers\Admin\HfsqlExportController::class, 'stream']);
        Route::get('/download',       [\App\Http\Controllers\Admin\HfsqlExportController::class, 'download']);
        Route::post('/sync-to-mysql', [\App\Http\Controllers\Admin\HfsqlExportController::class, 'syncToMysql']);
    });

    // ── Specialites ───────────────────────────────────────────────────────
    Route::prefix('dashboard/specialites')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Admin\SpecialiteController::class, 'index']);
        Route::get('/print',        [\App\Http\Controllers\Admin\SpecialiteController::class, 'printSpecialites']);
        Route::post('/store',       [\App\Http\Controllers\Admin\SpecialiteController::class, 'storeSpecialite']);
        Route::post('/update',      [\App\Http\Controllers\Admin\SpecialiteController::class, 'updateSpecialite']);
        Route::post('/delete/{id}', [\App\Http\Controllers\Admin\SpecialiteController::class, 'deleteSpecialite']);
        Route::post('/import',      [\App\Http\Controllers\Admin\SpecialiteController::class, 'importSpecialites']);
    });

    // ── Formateurs / Diplomes ─────────────────────────────────────────────
    Route::get('/dashboard/formateurs',  [\App\Http\Controllers\Admin\FormateurController::class, 'index']);
    Route::get('/dashboard/encadrement', [\App\Http\Controllers\Admin\FormateurController::class, 'index']);
    Route::post('/dashboard/formateurs/store', [\App\Http\Controllers\Admin\FormateurController::class, 'store']);
    Route::get('/dashboard/formateurs/show/{id}', [\App\Http\Controllers\Admin\FormateurController::class, 'show']);
    Route::post('/dashboard/formateurs/update', [\App\Http\Controllers\Admin\FormateurController::class, 'update']);
    Route::post('/dashboard/formateurs/delete/{id}', [\App\Http\Controllers\Admin\FormateurController::class, 'destroy']);

    Route::prefix('dashboard/diplomes')->group(function () {
        Route::get('/',                [\App\Http\Controllers\Admin\DiplomeController::class, 'index']);
        Route::get('/liste-2021-present', [\App\Http\Controllers\Admin\DiplomeController::class, 'liste2021']);
        Route::get('/generate/{id}',   [\App\Http\Controllers\Admin\DiplomeController::class, 'generate']);
        Route::get('/print/{id}',      [\App\Http\Controllers\Admin\DiplomeController::class, 'printDiploma']);
        Route::get('/show/{id}',       [\App\Http\Controllers\Admin\DiplomeController::class, 'show']);
        Route::post('/update',         [\App\Http\Controllers\Admin\DiplomeController::class, 'update']);
        Route::post('/delete/{id}',    [\App\Http\Controllers\Admin\DiplomeController::class, 'destroy']);
    });

    // ── Apprenants ────────────────────────────────────────────────────────
    Route::prefix('dashboard/apprenants')->group(function () {
        Route::get('/',                [\App\Http\Controllers\Admin\ApprenantController::class, 'index'])->middleware('secure.permission:view');
        Route::post('/store',          [\App\Http\Controllers\Admin\ApprenantController::class, 'store'])->middleware(['secure.permission:create', 'secure.scope']);
        Route::get('/show/{id}',       [\App\Http\Controllers\Admin\ApprenantController::class, 'show'])->middleware(['secure.permission:view', 'secure.ownership:App\Models\Apprenant,id']);
        Route::post('/update',         [\App\Http\Controllers\Admin\ApprenantController::class, 'update'])->middleware(['secure.permission:update', 'secure.ownership:App\Models\Apprenant,id']);
        Route::post('/delete/{id}',    [\App\Http\Controllers\Admin\ApprenantController::class, 'destroy'])->middleware(['secure.permission:delete', 'secure.ownership:App\Models\Apprenant,id']);
    });

    // ── Sections ──────────────────────────────────────────────────────────
    Route::prefix('dashboard/sections')->group(function () {
        Route::get('/',                [\App\Http\Controllers\Admin\SectionController::class, 'index'])->middleware('secure.permission:view');
        Route::post('/store',          [\App\Http\Controllers\Admin\SectionController::class, 'store'])->middleware(['secure.permission:create', 'secure.scope']);
        Route::get('/show/{id}',       [\App\Http\Controllers\Admin\SectionController::class, 'show'])->middleware(['secure.permission:view', 'secure.ownership:App\Models\Section,id']);
        Route::post('/update',         [\App\Http\Controllers\Admin\SectionController::class, 'update'])->middleware(['secure.permission:update', 'secure.ownership:App\Models\Section,id']);
        Route::post('/delete/{id}',    [\App\Http\Controllers\Admin\SectionController::class, 'destroy'])->middleware(['secure.permission:delete', 'secure.ownership:App\Models\Section,id']);
    });

    // ── Reporting ─────────────────────────────────────────────────────────
    Route::get('/dashboard/export/{type}', [\App\Http\Controllers\Admin\ReportController::class, 'export']);
    Route::get('/dashboard/print/{type}',  [\App\Http\Controllers\Admin\ReportController::class, 'printReport']);
    Route::get('/dashboard/pdf/{type}',    [\App\Http\Controllers\Admin\ReportController::class, 'pdfReport']);

    // ── Employee Space ─────────────────────────────────────────────────────
    Route::get('/dashboard/espace-employe',          [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'index']);
    Route::get('/dashboard/espace-employe/get/{id}', [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'getEmployee']);
    Route::post('/dashboard/espace-employe/update/{id}', [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'updateEmployee']);

    // ── Digital Cards Center ────────────────────────────────────────────────
    Route::get('/dashboard/digital-cards',             [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'digitalCards']);
    Route::get('/dashboard/digital-cards/trainee/{id}', [\App\Http\Controllers\Admin\EspaceEmployeController::class, 'getTrainee']);

    // ── Dashboard Builder ──────────────────────────────────────────────────
    Route::get('/dashboard/builder',                  [\App\Http\Controllers\Admin\DashboardBuilderController::class, 'index']);
    Route::get('/dashboard/builder/config/{userId}/{portalNum}', [\App\Http\Controllers\Admin\DashboardBuilderController::class, 'getPortalConfig']);
    Route::post('/dashboard/builder/save',             [\App\Http\Controllers\Admin\DashboardBuilderController::class, 'savePortalConfig']);

    // ── Audit Logs ─────────────────────────────────────────────────────────
    Route::get('/dashboard/audit-logs',              [\App\Http\Controllers\Admin\AuditController::class, 'index']);
    Route::get('/dashboard/audit-logs/export',       [\App\Http\Controllers\Admin\AuditController::class, 'export']);


    // ── Digital API Credentials / API Center ─────────────────────────
    Route::get('/dashboard/api-credentials',         [\App\Http\Controllers\Admin\ApiCenterController::class, 'index']);
    Route::get('/dashboard/api-center',              [\App\Http\Controllers\Admin\ApiCenterController::class, 'index']);
    Route::post('/dashboard/api-center/store',        [\App\Http\Controllers\Admin\ApiCenterController::class, 'store']);
    Route::post('/dashboard/api-center/update/{id}',  [\App\Http\Controllers\Admin\ApiCenterController::class, 'update']);
    Route::post('/dashboard/api-center/toggle/{id}',  [\App\Http\Controllers\Admin\ApiCenterController::class, 'toggle']);
    Route::post('/dashboard/api-center/delete/{id}',  [\App\Http\Controllers\Admin\ApiCenterController::class, 'destroy']);

    // ── Teacher/Employee Custom Dashboard Actions ──────────────────────────
    Route::post('/dashboard/formateur/grades/save', [DashboardController::class, 'saveTeacherGrades']);
    Route::post('/dashboard/formateur/attendance/save', [DashboardController::class, 'saveTeacherAttendance']);
    Route::post('/dashboard/employee/profile/update', [DashboardController::class, 'updateEmployeeProfile']);
    Route::post('/dashboard/employee/leaves/store', [DashboardController::class, 'storeLeaveRequest']);
    Route::post('/dashboard/employee/documents/request', [DashboardController::class, 'requestEmployeeDocument']);
    Route::post('/dashboard/employee/messages/send', [DashboardController::class, 'sendEmployeeMessage']);

    }); // Closing activation.check
});

// ═══════════════════════════════════════════════════════════════════════════
// §3  RESTful API V1 Endpoints (JWT Auth)
// ═══════════════════════════════════════════════════════════════════════════

foreach (['', 'sig/'] as $prefix) {
    Route::post($prefix . 'api/v1/auth/token', [\App\Http\Controllers\Api\JwtTokenController::class, 'issueToken']); // Exchange token
    Route::post($prefix . 'api/v1/preinscriptions/sync', [\App\Http\Controllers\Api\PreinscritSyncController::class, 'sync']);
    Route::post($prefix . 'api/v1/offers/sync', [\App\Http\Controllers\Api\OfferSyncController::class, 'sync']);
    
    // Public documentation routes
    Route::get($prefix . 'api/v1/docs', function () {
        return view('api.docs');
    });
    Route::get($prefix . 'api/v1/openapi.json', function () {
        return response()->file(public_path('api/v1/openapi.json'), [
            'Content-Type' => 'application/json; charset=UTF-8'
        ]);
    });

    Route::middleware(['api_auth', 'throttle:api'])->group(function () use ($prefix) {
        Route::get($prefix . 'api/v1/verify', [\App\Http\Controllers\Api\PortalApiController::class, 'verify']);
        Route::get($prefix . 'api/v1/stagiaires', [\App\Http\Controllers\Api\StagiairesApiController::class, 'index']);
        Route::get($prefix . 'api/v1/offres', [\App\Http\Controllers\Api\PortalApiController::class, 'getOffres']);
        Route::get($prefix . 'api/v1/employees', [\App\Http\Controllers\Api\EmployeesApiController::class, 'index']);
        
        // API Gateway & PWA Endpoints (Phase 5)
        Route::get($prefix . 'api/v1/hr/formateurs/vacant-hours', [\App\Http\Controllers\Api\FormateurApiController::class, 'vacantHours']);
        Route::get($prefix . 'api/v1/finance/reports/budget', [\App\Http\Controllers\Api\FinanceApiController::class, 'budgetReport']);
        Route::post($prefix . 'api/v1/assets/requests', [\App\Http\Controllers\Api\AssetApiController::class, 'requestEquipment']);
    });
}

// Password reset routes (Public)
Route::get('/reset-password', [LoginController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [LoginController::class, 'resetPassword'])->name('password.update');

// ═══════════════════════════════════════════════════════════════════════════
// §4  فضاء المتربص (Espace Stagiaire)
// ═══════════════════════════════════════════════════════════════════════════
Route::prefix('apprenant')->name('apprenant.')->group(function () {
    Route::get('/',      [\App\Http\Controllers\ApprenantController::class, 'index'])->name('dashboard');
    Route::get('/carte', [\App\Http\Controllers\ApprenantController::class, 'carteMetkoun'])->name('carte');
});

// ── /sig prefix mirrors for apprenant ─────────────────────────────────────
Route::prefix('sig/apprenant')->name('sig.apprenant.')->group(function () {
    Route::get('/',      [\App\Http\Controllers\ApprenantController::class, 'index'])->name('dashboard');
    Route::get('/carte', [\App\Http\Controllers\ApprenantController::class, 'carteMetkoun'])->name('carte');
});
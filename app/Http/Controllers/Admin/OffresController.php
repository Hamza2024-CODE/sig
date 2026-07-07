<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Academic\Services\OffresService;
use Illuminate\Http\Request;
use Exception;

class OffresController extends Controller
{
    protected OffresService $service;

    public function __construct(OffresService $service)
    {
        $this->service = $service;
        if (app()->runningInConsole()) { return; }
    }

    /**
     * Dashboard view of training offers list and statistics
     */
    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            $data = $this->service->getDashboardData($user, $request->all());
            return view('admin.offres.index', $data);
        } catch (Exception $e) {
            session(['flash_error' => $e->getMessage()]);
            return redirect()->route('dashboard');
        }
    }

    /**
     * Validation board for regional (DFEP) and central administrators
     */
    public function validation()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            $data = $this->service->getValidationDashboardData($user);
            return view('admin.offres.validation', $data);
        } catch (Exception $e) {
            session(['flash_error' => $e->getMessage()]);
            return redirect()->route('dashboard');
        }
    }

    /**
     * Store new training offer
     */
    public function storeOffre(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            $this->service->createOffer($request->all(), $user);
            session(['flash_success' => 'تم إدراج عرض التكوين الجديد بنجاح / Offre de formation ajoutée']);
        } catch (Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حفظ عرض التكوين: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    /**
     * Update an existing training offer
     */
    public function updateOffre(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            $this->service->updateOffer($request->all(), $user);
            session(['flash_success' => 'تم تحديث عرض التكوين بنجاح / Offre de formation modifiée']);
        } catch (Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء تحديث عرض التكوين: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    /**
     * Delete training offer
     */
    public function deleteOffre($id)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            $this->service->deleteOffer((int)$id, $user);
            session(['flash_success' => 'تم حذف عرض التكوين بنجاح / Offre de formation supprimée']);
        } catch (Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف عرض التكوين: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    /**
     * Submit offer to Regional Direction (DFEP)
     */
    public function soumettreOffre(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            $this->service->submitOffer((int)$request->input('id'), $user);
            session(['flash_success' => 'تم تقديم عرض التكوين للمديرية الولائية بنجاح / Offre soumise à la direction']);
        } catch (Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء تقديم العرض: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    /**
     * Validate/Reject offer by Regional Direction (DFEP)
     */
    public function validerDirection(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $id = (int)$request->input('id');
        $action = $request->input('action', '');
        $motif = ($action === 'rejeter') ? $request->input('motif_rejet', '') : null;

        try {
            $this->service->validateDirection($id, $action, $motif, $user);
            session([
                'flash_success' => ($action === 'approuver')
                    ? 'تمت المصادقة الولائية على عرض التكوين / Offre validée par la wilaya'
                    : 'تم رفض عرض التكوين ولائيا / Offre rejetée par la wilaya'
            ]);
        } catch (Exception $e) {
            session(['flash_error' => $e->getMessage()]);
        }

        return redirect()->back();
    }

    /**
     * Validate/Reject offer by Central Administration
     */
    public function validerCentrale(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $id = (int)$request->input('id');
        $action = $request->input('action', '');
        $motif = ($action === 'rejeter') ? $request->input('motif_rejet', '') : null;

        try {
            $this->service->validateCentral($id, $action, $motif, $user);
            session([
                'flash_success' => ($action === 'approuver')
                    ? 'تم القبول النهائي والمصادقة المركزية على عرض التكوين بنجاح / Offre approuvée par la centrale'
                    : 'تم رفض عرض التكوين مركزيا / Offre rejetée par la centrale'
            ]);
        } catch (Exception $e) {
            session(['flash_error' => $e->getMessage()]);
        }

        return redirect()->back();
    }

    /**
     * Render handbook training offers print list
     */
    public function printOffres()
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة دليل العروض معطلة من قبل مدير النظام / Printing is disabled by administrator.');
        }

        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            $offres = $this->service->getPrintOffres();
            return view('admin.offres.print', [
                'title' => 'طباعة دليل عروض التكوين - SGFEP',
                'offres' => $offres
            ]);
        } catch (Exception $e) {
            session(['flash_error' => $e->getMessage()]);
            return redirect()->back();
        }
    }
}

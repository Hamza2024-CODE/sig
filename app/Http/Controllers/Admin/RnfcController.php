<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ClassificationRnfc;
use App\Models\SecteurRnfc;
use App\Models\DomaineRnfc;
use App\Models\SousdomaineRnfc;
use App\Models\Specialite;
use Exception;

class RnfcController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $role = strtolower($user['role_code'] ?? '');

        // Load all data for listing in tabs
        $classifications = ClassificationRnfc::orderBy('IDclassification_rnfc', 'asc')->get();
        
        $secteurs = SecteurRnfc::with('classificationRnfc')
            ->orderBy('IDSecteur_rnfc', 'asc')
            ->get();
            
        $domaines = DomaineRnfc::with('secteurRnfc')
            ->orderBy('IDdomaine_rnfc', 'asc')
            ->get();
            
        $sousdomaines = SousdomaineRnfc::with('domaineRnfc')
            ->orderBy('IDsousdomaine_rnfc', 'asc')
            ->get();

        // Count for stats
        $counts = [
            'classifications' => $classifications->count(),
            'secteurs' => $secteurs->count(),
            'domaines' => $domaines->count(),
            'sousdomaines' => $sousdomaines->count(),
            'specialites' => DB::table('specialite')->whereNotNull('IDsousdomaine_rnfc')->where('IDsousdomaine_rnfc', '>', 0)->count()
        ];

        return view('admin.rnfc.index', compact(
            'classifications', 'secteurs', 'domaines', 'sousdomaines', 'counts', 'role'
        ));
    }

    // --- Classification CRUD ---
    public function storeClassification(Request $request)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        $request->validate([
            'Nom' => 'nullable|string|max:50',
            'NomFr' => 'required|string|max:50',
        ]);

        try {
            DB::transaction(function() use ($request) {
                $maxId = (int)DB::table('classification_rnfc')->lockForUpdate()->max('IDclassification_rnfc');
                $newId = max(1, $maxId + 1);

                DB::table('classification_rnfc')->insert([
                    'IDclassification_rnfc' => $newId,
                    'Nom' => $request->input('Nom') ?? '',
                    'NomFr' => $request->input('NomFr')
                ]);
            });

            return redirect()->back()->with('flash_success', 'تم إضافة التصنيف بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء الإضافة: ' . $e->getMessage());
        }
    }

    public function updateClassification(Request $request)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        $id = (int)$request->input('id');
        $request->validate([
            'Nom' => 'nullable|string|max:50',
            'NomFr' => 'required|string|max:50',
        ]);

        try {
            DB::table('classification_rnfc')
                ->where('IDclassification_rnfc', $id)
                ->update([
                    'Nom' => $request->input('Nom') ?? '',
                    'NomFr' => $request->input('NomFr')
                ]);

            return redirect()->back()->with('flash_success', 'تم تحديث التصنيف بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء التحديث: ' . $e->getMessage());
        }
    }

    public function deleteClassification($id)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        try {
            // Check if there are linked sectors
            $hasSectors = DB::table('secteur_rnfc')->where('IDclassification_rnfc', $id)->exists();
            if ($hasSectors) {
                return redirect()->back()->with('flash_error', 'لا يمكن حذف هذا التصنيف لوجود قطاعات مهنية مرتبطة به.');
            }

            DB::table('classification_rnfc')->where('IDclassification_rnfc', $id)->delete();
            return redirect()->back()->with('flash_success', 'تم حذف التصنيف بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء الحذف: ' . $e->getMessage());
        }
    }

    // --- Secteur CRUD ---
    public function storeSecteur(Request $request)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        $request->validate([
            'Nom' => 'nullable|string|max:100',
            'NomFr' => 'required|string|max:100',
            'code' => 'required|integer',
            'IDclassification_rnfc' => 'required|integer',
        ]);

        try {
            DB::transaction(function() use ($request) {
                $maxId = (int)DB::table('secteur_rnfc')->lockForUpdate()->max('IDSecteur_rnfc');
                $newId = max(1, $maxId + 1);

                DB::table('secteur_rnfc')->insert([
                    'IDSecteur_rnfc' => $newId,
                    'Nom' => $request->input('Nom') ?? '',
                    'NomFr' => $request->input('NomFr'),
                    'code' => $request->input('code'),
                    'IDclassification_rnfc' => $request->input('IDclassification_rnfc')
                ]);
            });

            return redirect()->back()->with('flash_success', 'تم إضافة القطاع بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء الإضافة: ' . $e->getMessage());
        }
    }

    public function updateSecteur(Request $request)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        $id = (int)$request->input('id');
        $request->validate([
            'Nom' => 'nullable|string|max:100',
            'NomFr' => 'required|string|max:100',
            'code' => 'required|integer',
            'IDclassification_rnfc' => 'required|integer',
        ]);

        try {
            DB::table('secteur_rnfc')
                ->where('IDSecteur_rnfc', $id)
                ->update([
                    'Nom' => $request->input('Nom') ?? '',
                    'NomFr' => $request->input('NomFr'),
                    'code' => $request->input('code'),
                    'IDclassification_rnfc' => $request->input('IDclassification_rnfc')
                ]);

            return redirect()->back()->with('flash_success', 'تم تحديث القطاع بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء التحديث: ' . $e->getMessage());
        }
    }

    public function deleteSecteur($id)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        try {
            $hasDomaines = DB::table('domaine_rnfc')->where('IDSecteur_rnfc', $id)->exists();
            if ($hasDomaines) {
                return redirect()->back()->with('flash_error', 'لا يمكن حذف هذا القطاع لوجود مجالات مرتبطة به.');
            }

            DB::table('secteur_rnfc')->where('IDSecteur_rnfc', $id)->delete();
            return redirect()->back()->with('flash_success', 'تم حذف القطاع بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء الحذف: ' . $e->getMessage());
        }
    }

    // --- Domaine CRUD ---
    public function storeDomaine(Request $request)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        $request->validate([
            'Nom' => 'nullable|string|max:200',
            'NomFr' => 'required|string|max:200',
            'code' => 'required|integer',
            'IDSecteur_rnfc' => 'required|integer',
        ]);

        try {
            DB::transaction(function() use ($request) {
                $maxId = (int)DB::table('domaine_rnfc')->lockForUpdate()->max('IDdomaine_rnfc');
                $newId = max(1, $maxId + 1);

                DB::table('domaine_rnfc')->insert([
                    'IDdomaine_rnfc' => $newId,
                    'Nom' => $request->input('Nom') ?? '',
                    'NomFr' => $request->input('NomFr'),
                    'code' => $request->input('code'),
                    'IDSecteur_rnfc' => $request->input('IDSecteur_rnfc')
                ]);
            });

            return redirect()->back()->with('flash_success', 'تم إضافة المجال بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء الإضافة: ' . $e->getMessage());
        }
    }

    public function updateDomaine(Request $request)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        $id = (int)$request->input('id');
        $request->validate([
            'Nom' => 'nullable|string|max:200',
            'NomFr' => 'required|string|max:200',
            'code' => 'required|integer',
            'IDSecteur_rnfc' => 'required|integer',
        ]);

        try {
            DB::table('domaine_rnfc')
                ->where('IDdomaine_rnfc', $id)
                ->update([
                    'Nom' => $request->input('Nom') ?? '',
                    'NomFr' => $request->input('NomFr'),
                    'code' => $request->input('code'),
                    'IDSecteur_rnfc' => $request->input('IDSecteur_rnfc')
                ]);

            return redirect()->back()->with('flash_success', 'تم تحديث المجال بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء التحديث: ' . $e->getMessage());
        }
    }

    public function deleteDomaine($id)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        try {
            $hasSousdomaines = DB::table('sousdomaine_rnfc')->where('IDdomaine_rnfc', $id)->exists();
            if ($hasSousdomaines) {
                return redirect()->back()->with('flash_error', 'لا يمكن حذف هذا المجال لوجود شعب دقيقة مرتبطة به.');
            }

            DB::table('domaine_rnfc')->where('IDdomaine_rnfc', $id)->delete();
            return redirect()->back()->with('flash_success', 'تم حذف المجال بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء الحذف: ' . $e->getMessage());
        }
    }

    // --- Sous-domaine CRUD ---
    public function storeSousdomaine(Request $request)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        $request->validate([
            'Nom' => 'nullable|string|max:300',
            'NomFr' => 'required|string|max:300',
            'code' => 'required|integer',
            'IDdomaine_rnfc' => 'required|integer',
        ]);

        try {
            DB::transaction(function() use ($request) {
                $maxId = (int)DB::table('sousdomaine_rnfc')->lockForUpdate()->max('IDsousdomaine_rnfc');
                $newId = max(1, $maxId + 1);

                DB::table('sousdomaine_rnfc')->insert([
                    'IDsousdomaine_rnfc' => $newId,
                    'Nom' => $request->input('Nom') ?? '',
                    'NomFr' => $request->input('NomFr'),
                    'code' => $request->input('code'),
                    'IDdomaine_rnfc' => $request->input('IDdomaine_rnfc')
                ]);
            });

            return redirect()->back()->with('flash_success', 'تم إضافة الشعبة الدقيقة بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء الإضافة: ' . $e->getMessage());
        }
    }

    public function updateSousdomaine(Request $request)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        $id = (int)$request->input('id');
        $request->validate([
            'Nom' => 'nullable|string|max:300',
            'NomFr' => 'required|string|max:300',
            'code' => 'required|integer',
            'IDdomaine_rnfc' => 'required|integer',
        ]);

        try {
            DB::table('sousdomaine_rnfc')
                ->where('IDsousdomaine_rnfc', $id)
                ->update([
                    'Nom' => $request->input('Nom') ?? '',
                    'NomFr' => $request->input('NomFr'),
                    'code' => $request->input('code'),
                    'IDdomaine_rnfc' => $request->input('IDdomaine_rnfc')
                ]);

            return redirect()->back()->with('flash_success', 'تم تحديث الشعبة الدقيقة بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء التحديث: ' . $e->getMessage());
        }
    }

    public function deleteSousdomaine($id)
    {
        if (!$this->checkWritePermission()) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية.');
        }

        try {
            $hasSpecialites = DB::table('specialite')->where('IDsousdomaine_rnfc', $id)->exists();
            if ($hasSpecialites) {
                return redirect()->back()->with('flash_error', 'لا يمكن حذف هذه الشعبة لوجود تخصصات مرتبطة بها.');
            }

            DB::table('sousdomaine_rnfc')->where('IDsousdomaine_rnfc', $id)->delete();
            return redirect()->back()->with('flash_success', 'تم حذف الشعبة الدقيقة بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء الحذف: ' . $e->getMessage());
        }
    }

    private function checkWritePermission(): bool
    {
        $user = session('user');
        if (!$user) return false;
        $role = strtolower($user['role_code'] ?? '');
        return in_array($role, ['admin', 'central', 'high_admin']);
    }
}

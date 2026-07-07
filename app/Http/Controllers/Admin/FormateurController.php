<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * FormateurController — تسيير كشف الموظفين / المكونين (Encadrement)
 *
 * القواعد الأساسية:
 * ✅ paginate(30) — لا fetchAll() على 84,279 سجل
 * ✅ فلترة بالبحث ورقم المؤسسة / الولاية
 * ✅ كاش عدد الإجمالي فقط (5 دقائق)
 * ❌ INSERT تلقائي محذوف نهائياً
 */
class FormateurController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request)
    {
        $user   = session('user') ?? [];
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        
        $role   = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        // ── بناء شروط الفلترة ────────────────────────────────────────────
        $where  = [];
        $params = [];

        // تقييد حسب الدور
        if (in_array($role, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin'])) {
            // لا قيود — الإدارة العليا ترى الكل
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $where[]  = 'et.IDDFEP = ?';
            $params[] = $dfepId;
        } elseif ($etabId > 0) {
            $where[]  = 'enc.IDetablissement = ?';
            $params[] = $etabId;

            // Restrict to Apprenticeship teachers if the user session has IDMode_formation = 10
            if ((int)session('user.IDMode_formation') === 10) {
                $where[] = "(
                    enc.IDEncadrement IN (
                        SELECT DISTINCT s2.IDEncadrement 
                        FROM section s2 
                        JOIN offre o2 ON s2.IDOffre = o2.IDOffre 
                        WHERE o2.IDMode_formation = 10 AND o2.IDEts_Form = ?
                    ) 
                    OR enc.IDEncadrement NOT IN (
                        SELECT DISTINCT s3.IDEncadrement FROM section s3 WHERE s3.IDEncadrement IS NOT NULL
                    )
                )";
                $params[] = $etabId;
            }
        } else {
            // دور غير معروف — لا بيانات
            $where[] = '1=0';
        }

        // فلتر البحث النصي (الاسم)
        $search = trim($request->query('search', ''));
        if ($search !== '') {
            $where[]  = "(enc.Nom LIKE ? OR enc.Prenom LIKE ? OR enc.Email LIKE ?)";
            $like     = "%{$search}%";
            $params   = array_merge($params, [$like, $like, $like]);
        }

        // فلتر الولاية
        $filterWilaya = (int)$request->query('filter_wilaya', 0);
        if ($filterWilaya > 0) {
            $where[]  = 'd.IDWilayaa = ?';
            $params[] = $filterWilaya;
        }

        // فلتر المؤسسة
        $filterEtab = (int)$request->query('filter_etab', 0);
        if ($filterEtab > 0 && $etabId === 0) {
            // يُسمح فقط إذا لم يكن المستخدم مُقيَّداً بمؤسسة واحدة
            $where[]  = 'enc.IDetablissement = ?';
            $params[] = $filterEtab;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // ── إجمالي عدد السجلات (مُكشَّن 5 دقائق) ───────────────────────
        $cacheKey   = 'formateurs_count_' . md5($whereSQL . implode(',', $params));
        $totalCount = Cache::remember($cacheKey, 300, function () use ($whereSQL, $params) {
            try {
                return (int) DB::selectOne(
                    "SELECT COUNT(*) as c
                     FROM encadrement enc
                     LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                     LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
                     {$whereSQL}",
                    $params
                )->c;
            } catch (\Throwable $e) {
                return 0;
            }
        });

        // ── Pagination ───────────────────────────────────────────────────
        $page       = max(1, (int)$request->query('page', 1));
        $totalPages = $totalCount > 0 ? (int)ceil($totalCount / self::PER_PAGE) : 1;
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        // ── الاستعلام الرئيسي — LIMIT صارم ─────────────────────────────
        $formateurs = [];
        try {
            $rows = DB::select(
                "SELECT enc.IDEncadrement  as id,
                        enc.Nom            as nom,
                        enc.Prenom         as prenom,
                        enc.Email          as email,
                        enc.Civ            as civ,
                        enc.Echlo          as echlo,
                        enc.DateInstall    as date_install,
                        sa.Nom             as situation,
                        et.Nom             as etab_ar,
                        w.Nom              as wilaya
                 FROM encadrement enc
                 LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                 LEFT JOIN dfep d            ON et.IDDFEP           = d.IDDFEP
                 LEFT JOIN wilaya w          ON d.IDWilayaa         = w.IDWilayaa
                 LEFT JOIN situationadministrat sa ON enc.IDSituationAdministrat = sa.IDSituationAdministrat
                 {$whereSQL}
                 ORDER BY enc.Nom ASC
                 LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
                $params
            );
            $formateurs = array_map(fn($r) => (array)$r, $rows);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[FormateurController] query error: ' . $e->getMessage());
        }

        // ── بيانات الفلاتر (ولايات + مؤسسات من ReferenceCache) ─────────
        $wilayas       = \App\Services\ReferenceCache::wilayas();
        $etablissements = match(true) {
            $dfepId > 0  => \App\Services\ReferenceCache::etablissementsForDfep($dfepId),
            $etabId > 0  => \App\Services\ReferenceCache::etablissementById($etabId),
            $filterWilaya > 0 && in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']) => DB::table('etablissement as et')
                ->join('dfep as d', 'et.IDDFEP', '=', 'd.IDDFEP')
                ->where('d.IDWilayaa', $filterWilaya)
                ->select('et.IDetablissement as id', 'et.Nom as nom_ar', 'et.Nom as nom')
                ->get()->map(fn($x)=>(array)$x)->toArray(),
            default      => \App\Services\ReferenceCache::etablissements(),
        };

        return $this->render('admin/formateurs/index', [
            'title'          => 'كشف الموظفين والمكونين / Registre des Encadreurs',
            'formateurs'     => $formateurs,
            'total_count'    => $totalCount,
            'page'           => $page,
            'total_pages'    => $totalPages,
            'per_page'       => self::PER_PAGE,
            'search'         => $search,
            'filter_etab'    => $filterEtab,
            'wilayas'        => $wilayas,
            'etablissements' => $etablissements,
            'role_code'      => $role,
        ]);
    }

    public function show($id)
    {
        try {
            $trainer = DB::table('encadrement')
                ->where('IDEncadrement', $id)
                ->first();

            if (!$trainer) {
                return response()->json(['success' => false, 'message' => 'الموظف غير موجود / Trainer not found'], 404);
            }

            if (!empty($trainer->nin)) {
                try {
                    $dec = \Illuminate\Support\Facades\Crypt::decryptString($trainer->nin);
                    if ($dec) {
                        $trainer->nin = $dec;
                    }
                } catch (\Exception $e) {}
            }

            return response()->json(['success' => true, 'data' => $trainer]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        if ($etabId > 0) {
            $request->merge(['etablissement_id' => $etabId]);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'email' => 'nullable|email|max:50',
            'civ' => 'required|integer',
            'tel' => 'nullable|string|max:20',
            'adres' => 'nullable|string|max:80',
            'date_install' => 'nullable|date',
            'echlo' => 'nullable|numeric',
            'etablissement_id' => 'required|integer',
            'taches' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $maxId = (int)DB::table('encadrement')->lockForUpdate()->max('IDEncadrement');
                $newId = max(1, $maxId + 1);

                DB::table('encadrement')->insert([
                    'IDEncadrement' => $newId,
                    'Nom' => $validated['nom'],
                    'Prenom' => $validated['prenom'],
                    'Email' => $validated['email'] ?? null,
                    'Civ' => $validated['civ'],
                    'Tel' => $validated['tel'] ?? null,
                    'Adres' => $validated['adres'] ?? null,
                    'DateInstall' => $validated['date_install'] ?? null,
                    'Echlo' => $validated['echlo'] ?? 0,
                    'IDetablissement' => $validated['etablissement_id'],
                    'IDSituationAdministrat' => 1,
                    'TachesPrincipale' => $validated['taches'] ?? null,
                    'Validation' => 1,
                ]);
            });

            session(['flash_success' => 'تم إضافة الموظف بنجاح / Encadreur ajouté avec succès']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء إضافة الموظف: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function update(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $validated = $request->validate([
            'id' => 'required|integer',
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'email' => 'nullable|email|max:50',
            'civ' => 'required|integer',
            'tel' => 'nullable|string|max:20',
            'adres' => 'nullable|string|max:80',
            'date_install' => 'nullable|date',
            'echlo' => 'nullable|numeric',
            'etablissement_id' => 'required|integer',
            'taches' => 'nullable|string',
        ]);

        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        if ($etabId > 0) {
            $validated['etablissement_id'] = $etabId;
            $idToCheck = (int)$validated['id'];
            $trainerEtab = DB::table('encadrement')->where('IDEncadrement', $idToCheck)->value('IDetablissement');
            if ((int)$trainerEtab !== $etabId) {
                session(['flash_error' => 'غير مصرح لك بتحديث بيانات هذا الموظف.']);
                return redirect()->back();
            }

            if ((int)($user['IDMode_formation'] ?? 0) === 10) {
                $isApprenticeshipTeacher = DB::table('section as s')
                    ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                    ->where('s.IDEncadrement', $idToCheck)
                    ->where('o.IDMode_formation', 10)
                    ->exists();
                $hasAnySections = DB::table('section')->where('IDEncadrement', $idToCheck)->exists();
                if ($hasAnySections && !$isApprenticeshipTeacher) {
                    session(['flash_error' => 'غير مصرح لك بتحديث بيانات أساتذة الأنماط الأخرى.']);
                    return redirect()->back();
                }
            }
        }

        try {
            DB::table('encadrement')
                ->where('IDEncadrement', $validated['id'])
                ->update([
                    'Nom' => $validated['nom'],
                    'Prenom' => $validated['prenom'],
                    'Email' => $validated['email'] ?? null,
                    'Civ' => $validated['civ'],
                    'Tel' => $validated['tel'] ?? null,
                    'Adres' => $validated['adres'] ?? null,
                    'DateInstall' => $validated['date_install'] ?? null,
                    'Echlo' => $validated['echlo'] ?? 0,
                    'IDetablissement' => $validated['etablissement_id'],
                    'TachesPrincipale' => $validated['taches'] ?? null,
                ]);

            session(['flash_success' => 'تم تحديث بيانات الموظف بنجاح / Encadreur modifié avec succès']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء تحديث بيانات الموظف: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $id = (int)$id;

        if ($etabId > 0) {
            $trainerEtab = DB::table('encadrement')->where('IDEncadrement', $id)->value('IDetablissement');
            if ((int)$trainerEtab !== $etabId) {
                session(['flash_error' => 'غير مصرح لك بحذف هذا الموظف.']);
                return redirect()->back();
            }

            if ((int)($user['IDMode_formation'] ?? 0) === 10) {
                $isApprenticeshipTeacher = DB::table('section as s')
                    ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                    ->where('s.IDEncadrement', $id)
                    ->where('o.IDMode_formation', 10)
                    ->exists();
                $hasAnySections = DB::table('section')->where('IDEncadrement', $id)->exists();
                if ($hasAnySections && !$isApprenticeshipTeacher) {
                    session(['flash_error' => 'غير مصرح لك بحذف أساتذة الأنماط الأخرى.']);
                    return redirect()->back();
                }
            }
        }

        try {
            // Guard: check if trainer is assigned to a section
            $hasSections = DB::table('section')->where('IDEncadrement', $id)->exists();
            if ($hasSections) {
                session(['flash_error' => 'لا يمكن حذف الموظف لكونه منسقاً أو مشرفاً على قسم تكويني نشط / Encadreur assigned to sections']);
            } else {
                DB::table('encadrement')->where('IDEncadrement', $id)->delete();
                session(['flash_success' => 'تم حذف الموظف بنجاح / Encadreur supprimé avec succès']);
            }
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف الموظف: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function ageDistribution(Request $request)
    {
        $user   = session('user') ?? [];
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        
        $role   = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        // ── شروط الفلترة ──────────────────────────────────────────────────
        $where  = [];
        $params = [];

        // تقييد الصلاحية والدور
        if (in_array($role, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin'])) {
            // الكل مسموح له
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $where[]  = 'et.IDDFEP = ?';
            $params[] = $dfepId;
        } elseif ($etabId > 0) {
            $where[]  = 'enc.IDetablissement = ?';
            $params[] = $etabId;

            if ((int)session('user.IDMode_formation') === 10) {
                $where[] = "(
                    enc.IDEncadrement IN (
                        SELECT DISTINCT s2.IDEncadrement 
                        FROM section s2 
                        JOIN offre o2 ON s2.IDOffre = o2.IDOffre 
                        WHERE o2.IDMode_formation = 10 AND o2.IDEts_Form = ?
                    ) 
                    OR enc.IDEncadrement NOT IN (
                        SELECT DISTINCT s3.IDEncadrement FROM section s3 WHERE s3.IDEncadrement IS NOT NULL
                    )
                )";
                $params[] = $etabId;
            }
        } else {
            $where[] = '1=0';
        }

        // قصر النتائج على الأساتذة فقط (تصفية العمال الإداريين والمصالح الأخرى)
        $where[] = 'enc.IDGrade IN (59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 135, 178)';

        // بحث نصي
        $search = trim($request->query('search', ''));
        if ($search !== '') {
            $where[]  = "(enc.Nom LIKE ? OR enc.Prenom LIKE ?)";
            $like     = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        // فلتر الولاية
        $filterWilaya = (int)$request->query('filter_wilaya', 0);
        if ($filterWilaya > 0) {
            $where[]  = 'd.IDWilayaa = ?';
            $params[] = $filterWilaya;
        }

        // فلتر المؤسسة
        $filterEtab = (int)$request->query('filter_etab', 0);
        if ($filterEtab > 0 && $etabId === 0) {
            $where[]  = 'enc.IDetablissement = ?';
            $params[] = $filterEtab;
        }

        // فلتر الشعبة
        $filterBranch = (int)$request->query('filter_branch', 0);
        if ($filterBranch > 0) {
            $where[]  = 'enc.IDBranche = ?';
            $params[] = $filterBranch;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // تكييش البيانات للطلبات العامة
        $cacheKey = 'formateurs_age_dist_data_' . md5($whereSQL . serialize($params));
        
        $cachedData = Cache::remember($cacheKey, 900, function() use ($whereSQL, $params) {
            $sql = "
                SELECT enc.Nom, enc.Prenom, enc.Specialite, enc.DateNais, enc.IDBranche,
                       br.Nom as branch_name, et.Nom as etab_name
                FROM encadrement enc
                LEFT JOIN branche br ON enc.IDBranche = br.IDBranche
                LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
                {$whereSQL}
            ";
            
            $rows = DB::select($sql, $params);
            
            $distribution = [];
            $teachersList = [];
            $totalCount = 0;
            $ageSum = 0;
            $ageCount = 0;
            $youngCount = 0; 
            $seniorCount = 0; 
            
            foreach ($rows as $r) {
                $branchName = $r->branch_name ?: 'شعبة غير محددة';
                $specialtyName = trim($r->Specialite) ?: 'تخصص غير محدد';
                
                $dob = $r->DateNais;
                $dobDecrypted = null;
                if ($dob) {
                    try {
                        $dobDecrypted = \Illuminate\Support\Facades\Crypt::decryptString($dob);
                    } catch (\Exception $e) {
                        $dobDecrypted = $dob;
                    }
                }
                
                $age = null;
                $ageBracket = 'غير محدد';
                
                if ($dobDecrypted) {
                    $dobClean = str_replace('/', '-', $dobDecrypted);
                    $time = strtotime($dobClean);
                    if ($time) {
                        $ageVal = date_diff(date_create($dobClean), date_create('today'))->y;
                        if ($ageVal < 18 || $ageVal > 75) {
                            $age = null;
                            $ageBracket = 'غير محدد';
                        } else {
                            $age = $ageVal;
                            $ageSum += $age;
                            $ageCount++;
                            
                            if ($age < 35) {
                                $youngCount++;
                            }
                            if ($age >= 50) {
                                $seniorCount++;
                            }
                            
                            if ($age < 25) {
                                $ageBracket = '<25';
                            } elseif ($age <= 29) {
                                $ageBracket = '25-29';
                            } elseif ($age <= 34) {
                                $ageBracket = '30-34';
                            } elseif ($age <= 39) {
                                $ageBracket = '35-39';
                            } elseif ($age <= 44) {
                                $ageBracket = '40-44';
                            } elseif ($age <= 49) {
                                $ageBracket = '45-49';
                            } elseif ($age <= 54) {
                                $ageBracket = '50-54';
                            } elseif ($age <= 59) {
                                $ageBracket = '55-59';
                            } else {
                                $ageBracket = '60+';
                            }
                        }
                    }
                }
                
                // التجميع الكلي
                if (!isset($distribution[$branchName][$specialtyName])) {
                    $distribution[$branchName][$specialtyName] = [
                        '<25' => 0,
                        '25-29' => 0,
                        '30-34' => 0,
                        '35-39' => 0,
                        '40-44' => 0,
                        '45-49' => 0,
                        '50-54' => 0,
                        '55-59' => 0,
                        '60+' => 0,
                        'غير محدد' => 0,
                        'total' => 0
                    ];
                }
                $distribution[$branchName][$specialtyName][$ageBracket]++;
                $distribution[$branchName][$specialtyName]['total']++;
                $totalCount++;
                
                // القائمة التفصيلية
                $teachersList[] = [
                    'name' => trim(($r->Nom ?? '') . ' ' . ($r->Prenom ?? '')),
                    'branch' => $branchName,
                    'specialty' => $specialtyName,
                    'etab' => $r->etab_name ?: 'غير محدد',
                    'age' => $age,
                    'bracket' => $ageBracket === 'غير محدد' ? 'غير محدد' : ($ageBracket === '<25' ? 'أقل من 25' : ($ageBracket === '60+' ? '60 فما فوق' : $ageBracket))
                ];
            }
            
            // ترتيب من الأكبر سناً للأصغر
            usort($teachersList, function($a, $b) {
                if ($a['age'] === null && $b['age'] === null) return 0;
                if ($a['age'] === null) return 1;
                if ($b['age'] === null) return -1;
                return $b['age'] <=> $a['age'];
            });
            
            $averageAge = $ageCount > 0 ? round($ageSum / $ageCount, 1) : 0;
            
            return compact('distribution', 'teachersList', 'totalCount', 'averageAge', 'youngCount', 'seniorCount');
        });

        // البجينة للقائمة التفصيلية
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($cachedData['teachersList']);
        $perPage = 50;
        $currentPageItems = $itemCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $paginatedTeachers = new \Illuminate\Pagination\LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        $paginatedTeachers->setPath($request->url());
        $paginatedTeachers->appends($request->all());

        // الفلاتر
        $wilayas = \App\Services\ReferenceCache::wilayas();
        $etablissements = match(true) {
            $dfepId > 0  => \App\Services\ReferenceCache::etablissementsForDfep($dfepId),
            $etabId > 0  => \App\Services\ReferenceCache::etablissementById($etabId),
            $filterWilaya > 0 && in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']) => DB::table('etablissement as et')
                ->join('dfep as d', 'et.IDDFEP', '=', 'd.IDDFEP')
                ->where('d.IDWilayaa', $filterWilaya)
                ->select('et.IDetablissement as id', 'et.Nom as nom_ar', 'et.Nom as nom')
                ->get()->map(fn($x)=>(array)$x)->toArray(),
            default      => \App\Services\ReferenceCache::etablissements(),
        };

        $branchesList = DB::table('branche')->select('IDBranche as id', 'Nom as nom_ar')->orderBy('Nom')->get()->map(fn($x)=>(array)$x)->toArray();

        return $this->render('admin/formateurs/age_distribution', [
            'title' => 'إحصائيات المكونين حسب السن والشعب التكوينية',
            'distribution' => $cachedData['distribution'],
            'paginatedTeachers' => $paginatedTeachers,
            'totalCount' => $cachedData['totalCount'],
            'averageAge' => $cachedData['averageAge'],
            'youngCount' => $cachedData['youngCount'],
            'seniorCount' => $cachedData['seniorCount'],
            'wilayas' => $wilayas,
            'etablissements' => $etablissements,
            'branchesList' => $branchesList,
            'role_code' => $role,
            'search' => $search,
            'filter_wilaya' => $filterWilaya,
            'filter_etab' => $filterEtab,
            'filter_branch' => $filterBranch,
            'active_tab' => $request->query('tab', 'aggregated')
        ]);
    }

    public function exportAgeDistribution(Request $request)
    {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        
        $user   = session('user') ?? [];
        $role   = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $where  = [];
        $params = [];

        if (in_array($role, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin'])) {
            // الكل مسموح له
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $where[]  = 'et.IDDFEP = ?';
            $params[] = $dfepId;
        } elseif ($etabId > 0) {
            $where[]  = 'enc.IDetablissement = ?';
            $params[] = $etabId;
            if ((int)session('user.IDMode_formation') === 10) {
                $where[] = "(enc.IDEncadrement IN (SELECT DISTINCT s2.IDEncadrement FROM section s2 JOIN offre o2 ON s2.IDOffre = o2.IDOffre WHERE o2.IDMode_formation = 10 AND o2.IDEts_Form = ?) OR enc.IDEncadrement NOT IN (SELECT DISTINCT s3.IDEncadrement FROM section s3 WHERE s3.IDEncadrement IS NOT NULL))";
                $params[] = $etabId;
            }
        } else {
            $where[] = '1=0';
        }

        // قصر النتائج على الأساتذة فقط (تصفية العمال الإداريين والمصالح الأخرى)
        $where[] = 'enc.IDGrade IN (59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 135, 178)';

        $search = trim($request->query('search', ''));
        if ($search !== '') {
            $where[]  = "(enc.Nom LIKE ? OR enc.Prenom LIKE ?)";
            $like     = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $filterWilaya = (int)$request->query('filter_wilaya', 0);
        if ($filterWilaya > 0) {
            $where[]  = 'd.IDWilayaa = ?';
            $params[] = $filterWilaya;
        }

        $filterEtab = (int)$request->query('filter_etab', 0);
        if ($filterEtab > 0 && $etabId === 0) {
            $where[]  = 'enc.IDetablissement = ?';
            $params[] = $filterEtab;
        }

        $filterBranch = (int)$request->query('filter_branch', 0);
        if ($filterBranch > 0) {
            $where[]  = 'enc.IDBranche = ?';
            $params[] = $filterBranch;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT enc.Nom, enc.Prenom, enc.Specialite, enc.DateNais, 
                   br.Nom as branch_name, et.Nom as etab_name
            FROM encadrement enc
            LEFT JOIN branche br ON enc.IDBranche = br.IDBranche
            LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
            LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
            {$whereSQL}
        ";

        $rows = DB::select($sql, $params);

        $list = [];
        foreach ($rows as $r) {
            $dob = $r->DateNais;
            $dobDecrypted = null;
            if ($dob) {
                try {
                    $dobDecrypted = \Illuminate\Support\Facades\Crypt::decryptString($dob);
                } catch (\Exception $e) {
                    $dobDecrypted = $dob;
                }
            }

            $age = null;
            $ageBracket = 'غير محدد';
            if ($dobDecrypted) {
                $dobClean = str_replace('/', '-', $dobDecrypted);
                $time = strtotime($dobClean);
                if ($time) {
                    $ageVal = date_diff(date_create($dobClean), date_create('today'))->y;
                    if ($ageVal < 18 || $ageVal > 75) {
                        $age = null;
                        $ageBracket = 'غير محدد';
                    } else {
                        $age = $ageVal;
                        if ($age < 25) {
                            $ageBracket = 'أقل من 25';
                        } elseif ($age <= 29) {
                            $ageBracket = '25-29';
                        } elseif ($age <= 34) {
                            $ageBracket = '30-34';
                        } elseif ($age <= 39) {
                            $ageBracket = '35-39';
                        } elseif ($age <= 44) {
                            $ageBracket = '40-44';
                        } elseif ($age <= 49) {
                            $ageBracket = '45-49';
                        } elseif ($age <= 54) {
                            $ageBracket = '50-54';
                        } elseif ($age <= 59) {
                            $ageBracket = '55-59';
                        } else {
                            $ageBracket = '60 فما فوق';
                        }
                    }
                }
            }

            $list[] = [
                'name' => trim(($r->Nom ?? '') . ' ' . ($r->Prenom ?? '')),
                'branch' => $r->branch_name ?: 'غير محدد',
                'specialty' => trim($r->Specialite) ?: 'غير محدد',
                'etab' => $r->etab_name ?: 'غير محدد',
                'age' => $age,
                'bracket' => $ageBracket
            ];
        }

        // ترتيب من الأكبر سناً للأصغر
        usort($list, function($a, $b) {
            if ($a['age'] === null && $b['age'] === null) return 0;
            if ($a['age'] === null) return 1;
            if ($b['age'] === null) return -1;
            return $b['age'] <=> $a['age'];
        });

        // تصدير كـ CSV مع UTF-8 BOM
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="liste_enseignants_par_age.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($list) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
            
            fputcsv($file, ['الاسم واللقب', 'الشعبة', 'التخصص', 'المؤسسة', 'السن', 'الفئة العمرية']);
            
            foreach ($list as $row) {
                fputcsv($file, [
                    $row['name'],
                    $row['branch'],
                    $row['specialty'],
                    $row['etab'],
                    $row['age'] !== null ? $row['age'] : 'غير محدد',
                    $row['bracket']
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

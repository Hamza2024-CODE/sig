<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GlobalSearchController — Unified cross-entity search endpoint.
 *
 * Provides a JSON endpoint that searches across: employees (encadrement),
 * establishments (etablissement), users (utilisateur), and modules/documents.
 * Results are role-scoped so each user only sees what they're authorized to see.
 */
class GlobalSearchController extends Controller
{
    /**
     * Maximum results per entity type.
     */
    private const MAX_PER_TYPE = 5;

    /**
     * Minimum query length to avoid table scans on empty queries.
     */
    private const MIN_QUERY_LENGTH = 2;

    /**
     * GET|POST /dashboard/search
     * Returns JSON array of categorized search results.
     */
    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));
        $role  = session('role', '');

        // Guard: empty or too-short queries
        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return response()->json(['results' => [], 'query' => $query]);
        }

        // Guard: prevent absurdly long queries
        if (mb_strlen($query) > 100) {
            $query = mb_substr($query, 0, 100);
        }

        $results = [];

        // ── 1. Employees (encadrement) ────────────────────────────────────────
        if (in_array($role, ['admin', 'directeur', 'inspecteur', 'rh'])) {
            $employees = DB::select(
                "SELECT IDEncadrement as id, CONCAT(Nom, ' ', Prenom) as label,
                        Poste as detail, 'employee' as type,
                        '/dashboard/employees' as url
                 FROM encadrement
                 WHERE (Nom LIKE ? OR Prenom LIKE ? OR NIN LIKE ? OR CONCAT(Nom,' ',Prenom) LIKE ?)
                 LIMIT " . self::MAX_PER_TYPE,
                array_fill(0, 4, "%{$query}%")
            );
            foreach ($employees as $row) {
                $results[] = $this->formatResult($row, 'fa-user-tie', 'موظف');
            }
        }

        // ── 2. Establishments (etablissement) ────────────────────────────────
        if (in_array($role, ['admin', 'directeur', 'inspecteur'])) {
            $etablissements = DB::select(
                "SELECT IDetablissement as id, Nom as label,
                        Wilaya as detail, 'etablissement' as type,
                        '/dashboard/etablissements' as url
                 FROM etablissement
                 WHERE (Nom LIKE ? OR CodeEtab LIKE ? OR Wilaya LIKE ?)
                 LIMIT " . self::MAX_PER_TYPE,
                array_fill(0, 3, "%{$query}%")
            );
            foreach ($etablissements as $row) {
                $results[] = $this->formatResult($row, 'fa-school', 'مؤسسة');
            }
        }

        // ── 3. Users (utilisateur) ───────────────────────────────────────────
        if ($role === 'admin') {
            $users = DB::select(
                "SELECT IDUtilisateur as id, COALESCE(Nom, NomUser) as label,
                        NomUser as detail, 'user' as type,
                        '/dashboard/utilisateurs' as url
                 FROM utilisateur
                 WHERE (NomUser LIKE ? OR Nom LIKE ?)
                 LIMIT " . self::MAX_PER_TYPE,
                array_fill(0, 2, "%{$query}%")
            );
            foreach ($users as $row) {
                $results[] = $this->formatResult($row, 'fa-user-shield', 'مستخدم');
            }
        }

        // ── 4. Documents / Modules ────────────────────────────────────────────
        if (in_array($role, ['admin', 'directeur', 'rh'])) {
            try {
                $documents = DB::select(
                    "SELECT id, titre as label, type_doc as detail,
                            'document' as type, '/dashboard/modules' as url
                     FROM documents
                     WHERE (titre LIKE ? OR type_doc LIKE ?)
                     LIMIT " . self::MAX_PER_TYPE,
                    array_fill(0, 2, "%{$query}%")
                );
                foreach ($documents as $row) {
                    $results[] = $this->formatResult($row, 'fa-file-alt', 'وثيقة');
                }
            } catch (\Exception $e) {
                // Documents table may not exist in all installations
            }
        }

        return response()->json([
            'results' => $results,
            'query'   => $query,
            'count'   => count($results),
        ]);
    }

    /**
     * Format a DB result row into a unified search result shape.
     */
    private function formatResult(object $row, string $icon, string $category): array
    {
        return [
            'id'       => $row->id ?? null,
            'label'    => $row->label ?? '',
            'detail'   => $row->detail ?? '',
            'type'     => $row->type ?? 'general',
            'url'      => $row->url ?? '#',
            'icon'     => $icon,
            'category' => $category,
        ];
    }
}

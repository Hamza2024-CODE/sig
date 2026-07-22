<?php

namespace App\Services\Employee;

use Illuminate\Support\Facades\Log;

/**
 * EmployeeQueryService
 *
 * All database access for the employee space lives here.
 * The Controller becomes thin — it only orchestrates, never writes SQL.
 *
 * Uses the existing LaravelDbAdapter (custom PDO wrapper) to stay compatible
 * with the current project architecture.
 */
class EmployeeQueryService
{
    /** Columns selected for the employee list (lightweight) */
    private const LIST_COLUMNS = '
        enc.IDEncadrement, enc.Nom, enc.Prenom, enc.NomFr, enc.PrenomFr,
        enc.Civ, enc.Specialite, enc.TachesPrincipale, enc.photo,
        enc.IDetablissement, enc.IDEts_Form, enc.IDSituationAdministrat,
        et.Nom AS etab_nom, et.NomFr AS etab_fr, et.IDNature_etsF,
        et_form.Nom AS etab_form_nom, et_form.NomFr AS etab_form_fr, et_form.IDNature_etsF AS form_IDNature_etsF,
        w.Nom AS wilaya_nom, w.IDWilayaa AS id_wilaya,
        w_form.Nom AS form_wilaya_nom, w_form.IDWilayaa AS form_id_wilaya,
        sa.Nom AS situation_admin_nom
    ';

    /** Columns selected for the employee detail view */
    private const DETAIL_COLUMNS = '
        enc.IDEncadrement, enc.Nom, enc.Prenom, enc.NomFr, enc.PrenomFr,
        enc.DateNais, enc.LieuNais, enc.Civ, enc.Tel, enc.Email, enc.Adres,
        enc.IDGrade, enc.IDFonctions, enc.Daterecr, enc.Specialite,
        enc.TachesPrincipale, enc.Echlo, enc.nbrEnf, enc.nbrenfscol,
        enc.IDSitfamille, enc.nss, enc.nin, enc.numActNaiss, enc.SitMilitaire,
        enc.IDetablissement, enc.IDEts_Form, enc.IDSituationAdministrat, enc.photo,
        enc.endicape, enc.IDEndicapePourcentage, enc.IDEndicapetype, enc.lieunaissetranger,
        enc.IDGradeDeb, enc.IDNiveau_Scol_enca, enc.IDDiplome, enc.IDBranche, enc.DureeDiplome,
        enc.DateInstall, enc.DateEchlon, enc.DateDebFonctions, enc.DateFinFonctions, enc.DateinstallPoste,
        et.Nom AS etab_nom, et.NomFr AS etab_fr, et.IDNature_etsF,
        et_form.Nom AS etab_form_nom, et_form.NomFr AS etab_form_fr, et_form.IDNature_etsF AS form_IDNature_etsF,
        w.Nom AS wilaya_nom, w.IDWilayaa AS id_wilaya,
        w_form.Nom AS form_wilaya_nom, w_form.IDWilayaa AS form_id_wilaya,
        g.Nom AS grade_nom,
        f.Nom AS fonction_nom,
        sa.Nom AS situation_admin_nom,
        g_deb.Nom AS grade_deb_nom,
        nse.Nom AS niveau_scol_nom,
        d.Nom AS diplome_nom,
        b.Nom AS branche_nom
    ';

    // ─────────────────────────────────────────────
    //  List (paginated)
    // ─────────────────────────────────────────────

    /**
     * Count total employees matching the given WHERE clauses.
     */
    public function count(string $whereClause, array $params): int
    {
        try {
            $db   = new \App\Core\LaravelDbAdapter();
            $sql  = "SELECT COUNT(*) FROM encadrement enc
                     LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                     LEFT JOIN etablissement et_form ON enc.IDEts_Form = et_form.IDetablissement
                     WHERE $whereClause";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            Log::error('EmployeeQueryService::count error', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Fetch a paginated list of employees (lightweight columns only).
     */
    public function paginate(string $whereClause, array $params, int $limit, int $offset): array
    {
        try {
            $db  = new \App\Core\LaravelDbAdapter();
            $sql = 'SELECT ' . self::LIST_COLUMNS . '
                    FROM encadrement enc
                    LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                    LEFT JOIN etablissement et_form ON enc.IDEts_Form = et_form.IDetablissement
                    LEFT JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
                    LEFT JOIN wilaya w_form ON et_form.IDDFEP = w_form.IDWilayaa
                    LEFT JOIN situationadministrat sa ON enc.IDSituationAdministrat = sa.IDSituationAdministrat
                    WHERE ' . $whereClause . '
                    ORDER BY enc.Nom ASC, enc.Prenom ASC
                    LIMIT ? OFFSET ?';

            $stmt = $db->prepare($sql);
            $i = 1;
            foreach ($params as $val) {
                $stmt->bindValue($i++, $val);
            }
            $stmt->bindValue($i++, $limit,  \PDO::PARAM_INT);
            $stmt->bindValue($i,   $offset, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Log::error('EmployeeQueryService::paginate error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ─────────────────────────────────────────────
    //  Single record
    // ─────────────────────────────────────────────

    /**
     * Find a single employee with full detail columns.
     * Returns null if not found.
     */
    public function findById(int $id): ?array
    {
        try {
            $db  = new \App\Core\LaravelDbAdapter();
            $sql = 'SELECT ' . self::DETAIL_COLUMNS . '
                    FROM encadrement enc
                    LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                    LEFT JOIN etablissement et_form ON enc.IDEts_Form = et_form.IDetablissement
                    LEFT JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
                    LEFT JOIN wilaya w_form ON et_form.IDDFEP = w_form.IDWilayaa
                    LEFT JOIN grade g ON enc.IDGrade = g.IDGrade
                    LEFT JOIN fonctions f ON enc.IDFonctions = f.IDFonctions
                    LEFT JOIN situationadministrat sa ON enc.IDSituationAdministrat = sa.IDSituationAdministrat
                    LEFT JOIN grade g_deb ON enc.IDGradeDeb = g_deb.IDGrade
                    LEFT JOIN niveau_scol_enca nse ON enc.IDNiveau_Scol_enca = nse.IDNiveau_Scol_enca
                    LEFT JOIN diplome d ON enc.IDDiplome = d.IDDiplome
                    LEFT JOIN branche b ON enc.IDBranche = b.IDBranche
                    WHERE enc.IDEncadrement = ?
                    LIMIT 1';

            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            // Decrypt encrypted fields inline
            $row = $this->decryptSensitiveFields($row);

            return $row;
        } catch (\Exception $e) {
            Log::error('EmployeeQueryService::findById error', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Find the minimal record needed to authorise an update (scope check).
     */
    public function findForUpdate(int $id): ?array
    {
        try {
            $db   = new \App\Core\LaravelDbAdapter();
            $sql  = 'SELECT enc.IDetablissement, et.IDDFEP
                     FROM encadrement enc
                     LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                     WHERE enc.IDEncadrement = ? LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            Log::error('EmployeeQueryService::findForUpdate error', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    // ─────────────────────────────────────────────
    //  Update
    // ─────────────────────────────────────────────

    /**
     * Apply a map of column => value updates to one employee record.
     * NIN is intentionally excluded from the $data map by the caller.
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db     = new \App\Core\LaravelDbAdapter();
            $parts  = [];
            $values = [];

            foreach ($data as $col => $val) {
                $parts[]  = "`$col` = ?";
                $values[] = $val;
            }
            $values[] = $id;

            $sql  = 'UPDATE encadrement SET ' . implode(', ', $parts) . ' WHERE IDEncadrement = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute($values);

            return true;
        } catch (\Exception $e) {
            Log::error('EmployeeQueryService::update error', ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    // ─────────────────────────────────────────────
    //  Decryption helper (centralised — single source of truth)
    // ─────────────────────────────────────────────

    /**
     * Attempt to decrypt NIN and DateNais fields if they are encrypted.
     * Silently ignores decryption failures (value stays as-is).
     */
    public function decryptSensitiveFields(array $row): array
    {
        foreach (['nin', 'DateNais'] as $field) {
            if (!empty($row[$field])) {
                try {
                    $dec = \Illuminate\Support\Facades\Crypt::decryptString($row[$field]);
                    if ($dec !== false && $dec !== '') {
                        $row[$field] = $dec;
                    }
                } catch (\Exception $e) {
                    // Value is already plain-text or could not be decrypted — leave as-is
                    Log::debug('EmployeeQueryService: decrypt failed for field ' . $field, [
                        'employee_id' => $row['IDEncadrement'] ?? '?',
                    ]);
                }
            }
        }
        return $row;
    }
}

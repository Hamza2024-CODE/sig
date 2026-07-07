<?php

namespace App\Http\Controllers\Inscription;

use App\Http\Controllers\Controller;
use App\Core\AuditLogger;

use PDO;

class PreInscriptionController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Pré-inscription SGFEP / التسجيل الأولي'
        ];

        return $this->render('inscription/wizard/index', $data, 'public');
    }

    public function store()
    {
        $uploadDir = __DIR__ . '/../../../../public/uploads/candidats/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $uploadedFiles = [];
        $errors = [];

        // Simple file processing placeholder
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $file) {
                if (is_array($file['name'])) {
                    for ($i = 0; $i < count($file['name']); $i++) {
                        if ($file['error'][$i] === UPLOAD_ERR_OK) {
                            $uploadedFiles[$key . '_' . $i] = 'uploaded'; // Just a placeholder for WINDEV
                        }
                    }
                } else {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $uploadedFiles[$key] = 'uploaded';
                    }
                }
            }
        }

        // The WINDEV structure for candidat
        $required = [
            'nin',
            'nom_ar',
            'prenom_ar',
            'nom_fr',
            'prenom_fr',
            'date_naissance',
            'sexe',
            'wilaya_residence_id',
            'commune_residence_id',
            'adresse',
            'telephone',
            'offre_id'
        ];

        foreach ($required as $field) {
            if (empty(request()->all()[$field])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Veuillez remplir tous les champs obligatoires.',
                    'field' => $field
                ], 422);
            }
        }

        try {
            $db = new \App\Core\LaravelDbAdapter();
            $db->beginTransaction();

            $offreId = (int)request()->all()['offre_id'];
            $stmtOffer = $db->prepare("SELECT IDOffre FROM offre WHERE IDOffre = ? LIMIT 1");
            $stmtOffer->execute([$offreId]);
            $offer = $stmtOffer->fetch(PDO::FETCH_ASSOC);

            if (!$offer) {
                $db->rollBack();
                return $this->json(['status' => 'error', 'message' => 'Offre de formation introuvable.'], 404);
            }

            $nin = trim(request()->all()['nin']);
            
            // Map inputs to WINDEV candidat table
            $candidateData = [
                'IDOffre' => $offreId,
                'Nom' => trim(request()->all()['nom_ar']),
                'Prenom' => trim(request()->all()['prenom_ar']),
                'NomFr' => trim(request()->all()['nom_fr']),
                'PrenomFr' => trim(request()->all()['prenom_fr']),
                'DateNais' => request()->all()['date_naissance'],
                'Civ' => (request()->all()['sexe'] === 'M') ? 1 : 2, // 1=Male, 2=Female in WINDEV
                'Tel' => trim(request()->all()['telephone']),
                'Nin' => $nin,
                'dateInscr' => date('Y-m-d'),
                'Validation' => 0, // 0 = Pending
                'ValidationDfp' => 0
            ];

            // Check if already applied
            $stmtCandidate = $db->prepare("SELECT IDCandidat, IDOffre FROM candidat WHERE Nin = ? ORDER BY IDCandidat DESC LIMIT 1");
            $stmtCandidate->execute([$nin]);
            $existing = $stmtCandidate->fetch(PDO::FETCH_ASSOC);

            if ($existing && $existing['IDOffre'] == $offreId) {
                // Already registered for this exact offer
                $db->commit();
                return $this->json([
                    'status' => 'success',
                    'message' => 'Pré-inscription déjà enregistrée pour cette offre.',
                    'numero_inscription' => 'WIN-' . $existing['IDCandidat'],
                    'files' => $uploadedFiles
                ]);
            }

            // Insert into candidat
            $stmtInsert = $db->prepare("
                INSERT INTO candidat
                (IDOffre, Nom, Prenom, NomFr, PrenomFr, DateNais, Civ, Tel, Nin, dateInscr, Validation, ValidationDfp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute(array_values($candidateData));
            $candidateId = (int)$db->lastInsertId();

            // Generate an inscription number based on WINDEV ID
            $numeroInscription = 'WIN-' . date('Y') . '-' . str_pad($candidateId, 6, '0', STR_PAD_LEFT);
            
            // Optionally update NumIns if needed
            $db->prepare("UPDATE candidat SET NumIns = ? WHERE IDCandidat = ?")->execute([$numeroInscription, $candidateId]);

            $db->commit();

            AuditLogger::log('CREATE', 'candidat', $candidateId, null, [
                'numero_inscription' => $numeroInscription,
                'candidat_id' => $candidateId,
                'offre_id' => $offreId
            ]);

            return $this->json([
                'status' => 'success',
                'message' => 'Pré-inscription enregistrée avec succès',
                'numero_inscription' => $numeroInscription,
                'files' => $uploadedFiles
            ]);
        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }

            return $this->json([
                'status' => 'error',
                'message' => 'Erreur lors de l’enregistrement de la pré-inscription: ' . $e->getMessage()
            ], 500);
        }
    }
}

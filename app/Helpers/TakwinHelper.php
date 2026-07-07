<?php

namespace App\Helpers;

use App\Core\Database;
use App\Helpers\EncryptionHelper;
use PDO;
use Exception;

class TakwinHelper
{
    /**
     * Get Takwin integration settings
     */
    public static function getSettings(): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM takwin_settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            // Return default settings
            return [
                'id' => null,
                'api_url' => 'https://takwin.dz/api',
                'api_token' => '',
                'sync_enabled' => 0,
                'last_sync_status' => null,
                'last_sync_message' => null,
                'last_sync_at' => null,
                'diploma_bg_url' => '',
                'diploma_border_color' => '#1e3a8a',
                'diploma_watermark_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Emblem_of_Algeria.svg',
                'diploma_primary_color' => '#1e3a8a'
            ];
        }

        // Decrypt the token if it exists
        if (!empty($settings['api_token'])) {
            $decrypted = EncryptionHelper::decrypt($settings['api_token']);
            $settings['api_token'] = $decrypted ?? '';
        }

        // Set default values for new columns if they are null
        $settings['diploma_bg_url'] = $settings['diploma_bg_url'] ?? '';
        $settings['diploma_border_color'] = $settings['diploma_border_color'] ?? '#1e3a8a';
        $settings['diploma_watermark_url'] = $settings['diploma_watermark_url'] ?? 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Emblem_of_Algeria.svg';
        $settings['diploma_primary_color'] = $settings['diploma_primary_color'] ?? '#1e3a8a';

        return $settings;
    }

    /**
     * Save Diploma Customization Settings
     */
    public static function saveDiplomaSettings(string $bgUrl, string $borderColor, string $watermarkUrl, string $primaryColor): bool
    {
        $db = Database::getInstance()->getConnection();

        // Check if settings record exists
        $stmt = $db->query("SELECT id FROM takwin_settings LIMIT 1");
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            $stmtUpdate = $db->prepare("
                UPDATE takwin_settings 
                SET diploma_bg_url = ?, diploma_border_color = ?, diploma_watermark_url = ?, diploma_primary_color = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            return $stmtUpdate->execute([$bgUrl, $borderColor, $watermarkUrl, $primaryColor, $exists['id']]);
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO takwin_settings (api_url, api_token, sync_enabled, diploma_bg_url, diploma_border_color, diploma_watermark_url, diploma_primary_color) 
                VALUES ('https://takwin.dz/api', '', 0, ?, ?, ?, ?)
            ");
            return $stmtInsert->execute([$bgUrl, $borderColor, $watermarkUrl, $primaryColor]);
        }
    }

    /**
     * Save Takwin integration settings
     */
    public static function saveSettings(string $apiUrl, string $apiToken, int $syncEnabled): bool
    {
        $db = Database::getInstance()->getConnection();
        
        // Encrypt the API Token using APP_KEY (deterministic/standard encryption)
        $encryptedToken = null;
        if (!empty($apiToken)) {
            $encryptedToken = EncryptionHelper::encryptDeterministic($apiToken);
        }

        // Check if settings record exists
        $stmt = $db->query("SELECT id FROM takwin_settings LIMIT 1");
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            $stmtUpdate = $db->prepare("
                UPDATE takwin_settings 
                SET api_url = ?, api_token = ?, sync_enabled = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            return $stmtUpdate->execute([$apiUrl, $encryptedToken, $syncEnabled, $exists['id']]);
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO takwin_settings (api_url, api_token, sync_enabled) 
                VALUES (?, ?, ?)
            ");
            return $stmtInsert->execute([$apiUrl, $encryptedToken, $syncEnabled]);
        }
    }

    /**
     * Run sync with Takwin API to fetch candidates
     * Returns an array with status, message, and details of synced candidates
     */
    public static function syncCandidates(bool $simulate = false): array
    {
        $settings = self::getSettings();
        $db = Database::getInstance()->getConnection();

        $apiUrl = rtrim($settings['api_url'], '/');
        $apiToken = $settings['api_token'];

        if (empty($apiUrl)) {
            return ['success' => false, 'message' => 'عنوان الـ API غير صالح / URL de l\'API invalide.'];
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        if (!empty($apiToken)) {
            $headers[] = 'Authorization: Bearer ' . $apiToken;
        }

        $candidatesData = [];
        $isSimulated = false;

        // If simulate is true, or if we have no token, or if call fails, we can allow simulation
        if ($simulate || empty($apiToken)) {
            $isSimulated = true;
            $candidatesData = self::getSimulatedCandidatesData();
        } else {
            // Actual API Call using cURL
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl . '/registrations');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Keep it resilient for dev/local

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($response === false) {
                    throw new Exception("cURL Error: " . $curlError);
                }

                if ($httpCode !== 200) {
                    throw new Exception("API returned HTTP code " . $httpCode . ". Response: " . substr($response, 0, 100));
                }

                $decoded = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON response received from API.");
                }

                // Expecting a structure: { "success": true, "data": [...] } or standard list [...]
                if (isset($decoded['data']) && is_array($decoded['data'])) {
                    $candidatesData = $decoded['data'];
                } elseif (is_array($decoded)) {
                    $candidatesData = $decoded;
                } else {
                    throw new Exception("Unexpected response format.");
                }

            } catch (Exception $e) {
                // If the real API call fails, update status to failed but fallback to simulation if simulation is allowed for presentation/testing
                $db->prepare("
                    UPDATE takwin_settings 
                    SET last_sync_status = 'failed', last_sync_message = ?, last_sync_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ")->execute([$e->getMessage(), $settings['id']]);

                return [
                    'success' => false,
                    'message' => 'فشل الاتصال بالـ API: ' . $e->getMessage() . '. تم إلغاء المزامنة تلقائياً.',
                    'details' => []
                ];
            }
        }

        // Process and Sync candidates into the 'candidat' table
        $addedCount = 0;
        $updatedCount = 0;
        $errors = [];

        // Fetch a default or random IDOffre from database to make sure it exists
        $stmtOffer = $db->query("SELECT IDOffre FROM offre LIMIT 1");
        $defaultOffer = $stmtOffer->fetch(PDO::FETCH_ASSOC);
        $defaultOfferId = $defaultOffer ? (int)$defaultOffer['IDOffre'] : 1;

        foreach ($candidatesData as $c) {
            try {
                $nin = trim($c['nin'] ?? '');
                if (empty($nin)) {
                    continue;
                }

                // Check if candidate already exists in database
                $checkStmt = $db->prepare("SELECT IDCandidat FROM candidat WHERE Nin = ?");
                $checkStmt->execute([$nin]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                // Map training offer code/id or default to $defaultOfferId
                $offreId = $defaultOfferId;
                if (!empty($c['offre_id'])) {
                    $stmtCheckOffre = $db->prepare("SELECT IDOffre FROM offre WHERE IDOffre = ? LIMIT 1");
                    $stmtCheckOffre->execute([(int)$c['offre_id']]);
                    if ($stmtCheckOffre->fetch()) {
                        $offreId = (int)$c['offre_id'];
                    }
                }

                $gender = (strtoupper($c['sexe'] ?? '') === 'F' || ($c['sexe'] ?? '') == 2) ? 2 : 1;

                if ($existing) {
                    // Update candidate
                    $stmtUpdate = $db->prepare("
                        UPDATE candidat 
                        SET Nom = ?, Prenom = ?, NomFr = ?, PrenomFr = ?, DateNais = ?, Civ = ?, Tel = ?
                        WHERE IDCandidat = ?
                    ");
                    $stmtUpdate->execute([
                        $c['nom_ar'] ?? '',
                        $c['prenom_ar'] ?? '',
                        $c['nom_fr'] ?? '',
                        $c['prenom_fr'] ?? '',
                        $c['date_naissance'] ?? '',
                        $gender,
                        $c['telephone'] ?? '',
                        $existing['IDCandidat']
                    ]);
                    $updatedCount++;
                } else {
                    // Insert candidate
                    $stmtInsert = $db->prepare("
                        INSERT INTO candidat (IDOffre, Nom, Prenom, NomFr, PrenomFr, DateNais, Civ, Tel, Nin, dateInscr, Validation, ValidationDfp)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 0, 0)
                    ");
                    $stmtInsert->execute([
                        $offreId,
                        $c['nom_ar'] ?? '',
                        $c['prenom_ar'] ?? '',
                        $c['nom_fr'] ?? '',
                        $c['prenom_fr'] ?? '',
                        $c['date_naissance'] ?? '',
                        $gender,
                        $c['telephone'] ?? '',
                        $nin
                    ]);

                    $newId = $db->lastInsertId();
                    $numIns = 'TAK-' . date('Y') . '-' . str_pad($newId, 6, '0', STR_PAD_LEFT);
                    $db->prepare("UPDATE candidat SET NumIns = ? WHERE IDCandidat = ?")->execute([$numIns, $newId]);

                    $addedCount++;
                }

            } catch (Exception $ex) {
                $errors[] = "NIN " . ($c['nin'] ?? 'unknown') . ": " . $ex->getMessage();
            }
        }

        // Update settings status
        $statusMsg = "تم جلب البيانات بنجاح. تمت إضافة {$addedCount} طلب جديد وتحديث {$updatedCount} طلب.";
        if ($isSimulated) {
            $statusMsg .= " (موضع محاكاة للـ API للتوضيح / Mode simulation API)";
        }
        if (!empty($errors)) {
            $statusMsg .= " مع وجود " . count($errors) . " أخطاء.";
        }

        $db->prepare("
            UPDATE takwin_settings 
            SET last_sync_status = 'success', last_sync_message = ?, last_sync_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ")->execute([$statusMsg, $settings['id']]);

        return [
            'success' => true,
            'message' => $statusMsg,
            'details' => [
                'added' => $addedCount,
                'updated' => $updatedCount,
                'errors' => $errors,
                'simulated' => $isSimulated
            ]
        ];
    }

    /**
     * Generate simulated/mock candidates data representing takwin.dz registrations
     */
    private static function getSimulatedCandidatesData(): array
    {
        return [
            [
                'nin' => '102938475610293847',
                'nom_ar' => 'بن علي',
                'prenom_ar' => 'أحمد',
                'nom_fr' => 'BENALI',
                'prenom_fr' => 'Ahmed',
                'date_naissance' => '1998-05-14',
                'sexe' => 'M',
                'adresse' => 'حي 500 مسكن، عمارة ب، الجلفة',
                'telephone' => '0661234567',
                'email' => 'ahmed.benali@gmail.com',
                'offre_id' => 1
            ],
            [
                'nin' => '203948576102938475',
                'nom_ar' => 'مرابط',
                'prenom_ar' => 'سارة',
                'nom_fr' => 'MERABET',
                'prenom_fr' => 'Sara',
                'date_naissance' => '2001-11-23',
                'sexe' => 'F',
                'adresse' => 'وسط المدينة، البويرة',
                'telephone' => '0772345678',
                'email' => 'sara.merabet@yahoo.fr',
                'offre_id' => 1
            ],
            [
                'nin' => '304958671029384756',
                'nom_ar' => 'سليماني',
                'prenom_ar' => 'مصطفى',
                'nom_fr' => 'SLIMANI',
                'prenom_fr' => 'Mustapha',
                'date_naissance' => '1999-09-02',
                'sexe' => 'M',
                'adresse' => 'شارع الشهداء، الجزائر العاصمة',
                'telephone' => '0553456789',
                'email' => 'mustapha.slimani@outlook.com',
                'offre_id' => 1
            ],
            [
                'nin' => '405968710293847561',
                'nom_ar' => 'حميدي',
                'prenom_ar' => 'فاطمة',
                'nom_fr' => 'HAMIDI',
                'prenom_fr' => 'Fatima',
                'date_naissance' => '2002-02-15',
                'sexe' => 'F',
                'adresse' => 'حي الصنوبر، وهران',
                'telephone' => '0664567890',
                'email' => 'fatima.hamidi@gmail.com',
                'offre_id' => 1
            ]
        ];
    }

    /**
     * Transliterate Arabic name to French standard format
     */
    public static function transliterateArabic(string $text): string
    {
        if (empty($text)) return '';
        
        // Normalize spaces and trim
        $text = trim(preg_replace('/\s+/', ' ', $text));
        
        // Common full names / words substitutions
        $composites = [
            'عبد القادر' => 'Abdelkader',
            'عبد الرحمن' => 'Abderrahmane',
            'عبد الرحيم' => 'Abderrahim',
            'عبد الله' => 'Abdellah',
            'عبد اللطيف' => 'Abdellatif',
            'عبد الحكيم' => 'Abdelhakim',
            'عبد المجيد' => 'Abdelmadjid',
            'عبد السلام' => 'Abdesselam',
            'عبد القوي' => 'Abdelkaoui',
            'عبد الحميد' => 'Abdelhamid',
            'عبد العزيز' => 'Abdelaziz',
            'عبد الكريم' => 'Abdelkrim',
            'عبد المالك' => 'Abdelmalek',
            'عبد الجليل' => 'Abdeldjelil',
            'عبد الباقي' => 'Abdelbaki',
            'عبد الغني' => 'Abdelghani',
            'عبد الوهاب' => 'Abdelouahab',
            'أبو بكر' => 'Aboubaker',
            'ابو بكر' => 'Aboubaker',
            'بوبكر' => 'Boubakar',
            'صديق' => 'Seddik',
            'محمد' => 'Mohamed',
            'أحمد' => 'Ahmed',
            'احمد' => 'Ahmed',
            'علي' => 'Ali',
            'عمر' => 'Omar',
            'عثمان' => 'Othmane',
            'حمزة' => 'Hamza',
            'براهيم' => 'Brahim',
            'إبراهيم' => 'Ibrahim',
            'ابراهيم' => 'Ibrahim',
            'زكريا' => 'Zakaria',
            'زكرياء' => 'Zakaria',
            'يوسف' => 'Youssef',
            'مصطفى' => 'Mustapha',
            'رشيد' => 'Rachid',
            'سعيد' => 'Said',
            'سعاد' => 'Souad',
            'خالد' => 'Khaled',
            'جمال' => 'Djamel',
            'امين' => 'Amine',
            'أمين' => 'Amine',
            'ياسين' => 'Yacine',
            'مراد' => 'Mourad',
            'سليم' => 'Selim',
            'سمير' => 'Samir',
            'امال' => 'Amel',
            'أمل' => 'Amel',
            'مريم' => 'Meryem',
            'نور' => 'Nour',
            'الطيب' => 'Tayeb',
            'بلقاسم' => 'Belkacem',
            'الحاج' => 'El Hadj',
            'قادر' => 'Kader',
            'عبد ' => 'Abdel ',
            'عبد' => 'Abdel',
            'أبو ' => 'Abou ',
            'ابو ' => 'Abou ',
            'الدين' => 'Eddine',
            'الله' => 'Allah',
        ];
        
        foreach ($composites as $ar => $lat) {
            $text = str_replace($ar, $lat, $text);
        }
        
        // Mappings for individual Arabic characters
        $map = [
            'أ' => 'A', 'إ' => 'I', 'آ' => 'A', 'ا' => 'A',
            'ب' => 'B',
            'ت' => 'T', 'ة' => 'T',
            'ث' => 'Th',
            'ج' => 'Dj',
            'ح' => 'H',
            'خ' => 'Kh',
            'د' => 'D',
            'ذ' => 'Dh',
            'ر' => 'R',
            'ز' => 'Z',
            'س' => 'S',
            'ش' => 'Ch',
            'ص' => 'S',
            'ض' => 'D',
            'ط' => 'T',
            'ظ' => 'Dh',
            'ع' => 'A',
            'غ' => 'Gh',
            'ف' => 'F',
            'ق' => 'K',
            'ك' => 'K',
            'ل' => 'L',
            'م' => 'M',
            'ن' => 'N',
            'ه' => 'H',
            'و' => 'Ou',
            'ي' => 'I', 'ى' => 'A',
            'ئ' => 'I', 'ؤ' => 'Ou', 'ء' => 'A',
            'لا' => 'La'
        ];
        
        $result = '';
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            if (preg_match('/[a-zA-Z\s\-]/', $char)) {
                $result .= $char;
            } elseif (isset($map[$char])) {
                $result .= $map[$char];
            }
        }
        
        return ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $result))));
    }

    /**
     * Detect Wilaya details based on establishment name
     */
    public static function detectWilayaFromEtab(string $etabNom): array
    {
        // Default fallback to Oran
        $wilayaAr = 'وهران';
        $wilayaFr = 'Oran';
        
        $keywords = [
            'أدرار' => ['ar' => 'أدرار', 'fr' => 'Adrar'],
            'Adrar' => ['ar' => 'أدرار', 'fr' => 'Adrar'],
            'الشلف' => ['ar' => 'الشلف', 'fr' => 'Chlef'],
            'Chlef' => ['ar' => 'الشلف', 'fr' => 'Chlef'],
            'الأغواط' => ['ar' => 'الأغواط', 'fr' => 'Laghouat'],
            'Laghouat' => ['ar' => 'الأغواط', 'fr' => 'Laghouat'],
            'أم البواقي' => ['ar' => 'أم البواقي', 'fr' => 'Oum El Bouaghi'],
            'باتنة' => ['ar' => 'باتنة', 'fr' => 'Batna'],
            'Batna' => ['ar' => 'باتنة', 'fr' => 'Batna'],
            'بجاية' => ['ar' => 'بجاية', 'fr' => 'Béjaïa'],
            'Bejaia' => ['ar' => 'بجاية', 'fr' => 'Béjaïa'],
            'بسكرة' => ['ar' => 'بسكرة', 'fr' => 'Biskra'],
            'Biskra' => ['ar' => 'بسكرة', 'fr' => 'Biskra'],
            'بشار' => ['ar' => 'بشار', 'fr' => 'Béchar'],
            'Bechar' => ['ar' => 'بشار', 'fr' => 'Béchar'],
            'البليدة' => ['ar' => 'البليدة', 'fr' => 'Blida'],
            'البليده' => ['ar' => 'البليدة', 'fr' => 'Blida'],
            'Blida' => ['ar' => 'البليدة', 'fr' => 'Blida'],
            'البويرة' => ['ar' => 'البويرة', 'fr' => 'Bouira'],
            'Bouira' => ['ar' => 'البويرة', 'fr' => 'Bouira'],
            'تمنراست' => ['ar' => 'تمنراست', 'fr' => 'Tamanrasset'],
            'تبسة' => ['ar' => 'تبسة', 'fr' => 'Tébessa'],
            'تلمسان' => ['ar' => 'تلمسان', 'fr' => 'Tlemcen'],
            'Tlemcen' => ['ar' => 'تلمسان', 'fr' => 'Tlemcen'],
            'تيارت' => ['ar' => 'تيارت', 'fr' => 'Tiaret'],
            'Tiaret' => ['ar' => 'تيارت', 'fr' => 'Tiaret'],
            'تيزي وزو' => ['ar' => 'تيزي وزو', 'fr' => 'Tizi Ouzou'],
            'الجزائر' => ['ar' => 'الجزائر', 'fr' => 'Alger'],
            'رويبة' => ['ar' => 'الجزائر', 'fr' => 'Alger'],
            'Rouiba' => ['ar' => 'الجزائر', 'fr' => 'Alger'],
            'Alger' => ['ar' => 'الجزائر', 'fr' => 'Alger'],
            'الجلفة' => ['ar' => 'الجلفة', 'fr' => 'Djelfa'],
            'Djelfa' => ['ar' => 'الجلفة', 'fr' => 'Djelfa'],
            'جيجل' => ['ar' => 'جيجل', 'fr' => 'Jijel'],
            'Jijel' => ['ar' => 'جيجل', 'fr' => 'Jijel'],
            'سطيف' => ['ar' => 'سطيف', 'fr' => 'Sétif'],
            'Setif' => ['ar' => 'سطيف', 'fr' => 'Sétif'],
            'سعيدة' => ['ar' => 'سعيدة', 'fr' => 'Saïda'],
            'سعيده' => ['ar' => 'سعيدة', 'fr' => 'Saïda'],
            'Saida' => ['ar' => 'سعيدة', 'fr' => 'Saïda'],
            'سكيكدة' => ['ar' => 'سكيكدة', 'fr' => 'Skikda'],
            'سيدي بلعباس' => ['ar' => 'سيدي بلعباس', 'fr' => 'Sidi Bel Abbès'],
            'عنابة' => ['ar' => 'عنابة', 'fr' => 'Annaba'],
            'Annaba' => ['ar' => 'عنابة', 'fr' => 'Annaba'],
            'قالمة' => ['ar' => 'قالمة', 'fr' => 'Guelma'],
            'قسنطينة' => ['ar' => 'قسنطينة', 'fr' => 'Constantine'],
            'Constantine' => ['ar' => 'قسنطينة', 'fr' => 'Constantine'],
            'المدية' => ['ar' => 'المدية', 'fr' => 'Médéa'],
            'مستغانم' => ['ar' => 'مستغانم', 'fr' => 'Mostaganem'],
            'المسيلة' => ['ar' => 'المسيلة', 'fr' => 'M\'Sila'],
            'معسكر' => ['ar' => 'معسكر', 'fr' => 'Mascara'],
            'ورقلة' => ['ar' => 'ورقلة', 'fr' => 'Ouargla'],
            'وهران' => ['ar' => 'وهران', 'fr' => 'Oran'],
            'Oran' => ['ar' => 'وهران', 'fr' => 'Oran'],
            'البيض' => ['ar' => 'البيض', 'fr' => 'El Bayadh'],
            'إليزي' => ['ar' => 'إليزي', 'fr' => 'Illizi'],
            'برج بوعريريج' => ['ar' => 'برج بوعريريج', 'fr' => 'Bordj Bou Arréridj'],
            'بومرداس' => ['ar' => 'بومرداس', 'fr' => 'Boumerdès'],
            'الطارف' => ['ar' => 'الطارف', 'fr' => 'El Tarf'],
            'تندوف' => ['ar' => 'تندوف', 'fr' => 'Tindouf'],
            'تيسمسيلت' => ['ar' => 'تيسمسيلت', 'fr' => 'Tissemsilt'],
            'الوادي' => ['ar' => 'الوادي', 'fr' => 'El Oued'],
            'خنشلة' => ['ar' => 'خنشلة', 'fr' => 'Khenchela'],
            'سوق أهراس' => ['ar' => 'سوق أهراس', 'fr' => 'Souk Ahras'],
            'تيبازة' => ['ar' => 'تيبازة', 'fr' => 'Tipaza'],
            'ميلة' => ['ar' => 'ميلة', 'fr' => 'Mila'],
            'عين الدفلى' => ['ar' => 'عين الدفلى', 'fr' => 'Aïn Defla'],
            'النعامة' => ['ar' => 'النعامة', 'fr' => 'Naâma'],
            'عين تموشنت' => ['ar' => 'عين تموشنت', 'fr' => 'Aïn Témouchent'],
            'غرداية' => ['ar' => 'غرداية', 'fr' => 'Ghardaïa'],
            'غليزان' => ['ar' => 'غليزان', 'fr' => 'Relizane']
        ];
        
        foreach ($keywords as $kw => $val) {
            if (mb_strpos($etabNom, $kw, 0, 'UTF-8') !== false) {
                $wilayaAr = $val['ar'];
                $wilayaFr = $val['fr'];
                break;
            }
        }
        
        return ['ar' => $wilayaAr, 'fr' => $wilayaFr];
    }

    /**
     * Fix double UTF-8 / CP850 encoding mismatch issues in French text
     */
    public static function fixDoubleUtf8(?string $str): string
    {
        if (empty($str)) {
            return '';
        }
        
        $replacements = [
            'ÔÇÖ' => "'",
            '├á' => 'à',
            '├®' => 'é',
            '├¿' => 'è',
            '├┤' => 'ô',
            'ÔÇô' => '–',
            '├º' => 'ç',
            '├«' => 'î',
            '├¬' => 'ê',
            '├ó' => 'â',
            '├╣' => 'ù',
            '├╗' => 'û',
            '├»' => 'ï',
            'ÔÇª' => '...',
            '├À' => 'À',
            'ÔÇÿ' => "'",
            'ÔÇÄ' => '',
            '├½' => 'ë',
            'ÔÇó' => '•',
        ];

        return strtr($str, $replacements);
    }
}


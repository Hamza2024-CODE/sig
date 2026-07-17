<?php

namespace App\Domains\Academic\Services;

class GradingSystemService
{
    /**
     * Calculate module grade before and after remedial.
     * Returns an array with ['moy_avr', 'moy_apr', 'is_eliminated']
     */
    public function calculateModuleGrade(array $grades, array $config, int $modeFormation = 1): array
    {
        $type = $grades['type_matiere'] ?? 'theorique';
        
        $cc1  = isset($grades['cc1']) && $grades['cc1'] !== '' ? (float)$grades['cc1'] : null;
        $cc2  = isset($grades['cc2']) && $grades['cc2'] !== '' ? (float)$grades['cc2'] : null;
        $exam = isset($grades['exam']) && $grades['exam'] !== '' ? (float)$grades['exam'] : null;
        $ratt = isset($grades['rattrapage']) && $grades['rattrapage'] !== '' ? (float)$grades['rattrapage'] : null;
        
        $eliminationThreshold = (float)($config['modes'][$modeFormation]['elimination_threshold'] ?? $config['semester']['elimination_threshold'] ?? 5.0);

        if ($type === 'stage_pratique') {
            $stage = isset($grades['stage']) && $grades['stage'] !== '' ? (float)$grades['stage'] : 0.0;
            return [
                'moy_avr' => $stage,
                'moy_apr' => $stage,
                'is_eliminated' => $stage < $eliminationThreshold
            ];
        }

        if ($type === 'memoire') {
            $mem  = isset($grades['memoire']) && $grades['memoire'] !== '' ? (float)$grades['memoire'] : 0.0;
            $sout = isset($grades['soutenance']) && $grades['soutenance'] !== '' ? (float)$grades['soutenance'] : 0.0;
            
            // Default thesis formula: memoire * 60% + soutenance * 40%
            $avg = round(($mem * 0.60 + $sout * 0.40) * 100) / 100;
            return [
                'moy_avr' => $avg,
                'moy_apr' => $avg,
                'is_eliminated' => $avg < $eliminationThreshold
            ];
        }

        // --- Distance Learning (modes 18, 21) ---
        if (in_array($modeFormation, [18, 21])) {
            $dlPlatform   = (float)($config['modes'][$modeFormation]['dl_platform_activity'] ?? $config['distance_learning']['weights']['platform_activity'] ?? 0.3);
            $dlAssign     = (float)($config['modes'][$modeFormation]['dl_assignments'] ?? $config['distance_learning']['weights']['assignments'] ?? 0.3);
            $dlWritten    = (float)($config['modes'][$modeFormation]['dl_written_exam'] ?? $config['distance_learning']['weights']['written_exam'] ?? 0.4);

            $valPlatform = $cc1 !== null ? $cc1 : 0.0;
            $valAssign   = $cc2 !== null ? $cc2 : 0.0;
            $valWritten  = $exam !== null ? $exam : 0.0;

            $moyAvr = ($valPlatform * $dlPlatform) + ($valAssign * $dlAssign) + ($valWritten * $dlWritten);
            $moyAvr = round($moyAvr * 100) / 100;

            $bestWritten = $valWritten;
            if ($ratt !== null) {
                $bestWritten = max($valWritten, $ratt);
            }

            $moyApr = ($valPlatform * $dlPlatform) + ($valAssign * $dlAssign) + ($bestWritten * $dlWritten);
            $moyApr = round($moyApr * 100) / 100;

            return [
                'moy_avr' => $moyAvr,
                'moy_apr' => $moyApr,
                'is_eliminated' => ($moyApr < $eliminationThreshold)
            ];
        }

        // --- Standard modules: theorique, tp, oral (Prèsentiel / Apprentissage) ---
        $ccWeight   = (float)($config['modes'][$modeFormation]['continuous_assessment_weight'] ?? $config['module_grade']['continuous_assessment_weight'] ?? 0.4);
        $examWeight = (float)($config['modes'][$modeFormation]['exam_weight'] ?? $config['module_grade']['exam_weight'] ?? 0.6);
        $divisor    = (float)($config['modes'][$modeFormation]['divisor'] ?? $config['module_grade']['divisor'] ?? 1.0);

        // Compute CC average
        $ccAvg = 0.0;
        if ($cc1 !== null && $cc2 !== null) {
            $ccAvg = ($cc1 + $cc2) / 2.0;
        } elseif ($cc1 !== null) {
            $ccAvg = $cc1;
        } elseif ($cc2 !== null) {
            $ccAvg = $cc2;
        }

        $examVal = $exam !== null ? $exam : 0.0;

        // If it is BEP, ECF/comprehensive exam and its remedial are out of 40, so scale them to out of 20
        if ($modeFormation === 8) {
            $examValScaled = $examVal / 2.0;
            $rattValScaled = $ratt !== null ? $ratt / 2.0 : null;
        } else {
            $examValScaled = $examVal;
            $rattValScaled = $ratt;
        }

        // Calculate average before remedial
        $moyAvr = (($ccAvg * $ccWeight) + ($examValScaled * $examWeight)) / $divisor;
        $moyAvr = round($moyAvr * 100) / 100;

        // Calculate average after remedial
        $bestExamScaled = $examValScaled;
        if ($rattValScaled !== null) {
            $bestExamScaled = max($examValScaled, $rattValScaled);
        }

        $moyApr = (($ccAvg * $ccWeight) + ($bestExamScaled * $examWeight)) / $divisor;
        $moyApr = round($moyApr * 100) / 100;

        // If average is above passing threshold, it's not eliminated even if exam was low,
        // unless the final module average is below the elimination threshold.
        $isEliminated = ($moyApr < $eliminationThreshold);

        return [
            'moy_avr' => $moyAvr,
            'moy_apr' => $moyApr,
            'is_eliminated' => $isEliminated
        ];
    }

    /**
     * Calculate Semester GPA.
     * $modules: array of items: ['coefficient' => int, 'note_avr' => float, 'note_apr' => float]
     */
    public function calculateSemesterGpa(array $modules, ?float $noteStage, $mode, array $config): array
    {
        $modeStr = is_string($mode) ? strtolower(trim($mode)) : (string)$mode;
        $totalCoef = 0.0;
        $sumAvr    = 0.0;
        $sumApr    = 0.0;

        $hasElimination = false;
        $modeId = (int)$mode;
        $eliminationThreshold = (float)($config['modes'][$modeId]['elimination_threshold'] ?? $config['semester']['elimination_threshold'] ?? 5.0);

        foreach ($modules as $m) {
            $coef = (float)($m['coefficient'] ?? 1.0);
            $noteAvr = (float)($m['note_avr'] ?? 0.0);
            $noteApr = (float)($m['note_apr'] ?? 0.0);

            $totalCoef += $coef;
            $sumAvr    += ($noteAvr * $coef);
            $sumApr    += ($noteApr * $coef);

            if ($noteApr < $eliminationThreshold) {
                $hasElimination = true;
            }
        }

        // Mode 2 is Apprentissage in legacy sgfep_windev, 10 in new list
        if ($modeId === 10 || $modeId === 2 || $modeStr === 'apprentissage' || $modeStr === 'تمهين') {
            $companyCoef = (float)($config['modes'][$modeId]['company_coefficient'] ?? $config['semester']['apprenticeship']['company_coefficient'] ?? 4.0);
            $stageGrade  = $noteStage !== null ? $noteStage : 0.0;

            $totalCoef += $companyCoef;
            $sumAvr    += ($stageGrade * $companyCoef);
            $sumApr    += ($stageGrade * $companyCoef);

            if ($stageGrade < $eliminationThreshold) {
                $hasElimination = true;
            }
        }

        $gpaAvr = $totalCoef > 0 ? round(($sumAvr / $totalCoef) * 100) / 100 : 0.0;
        $gpaApr = $totalCoef > 0 ? round(($sumApr / $totalCoef) * 100) / 100 : 0.0;

        $passingThreshold = (float)($config['modes'][$modeId]['passing_gpa_threshold'] ?? $config['semester']['passing_gpa_threshold'] ?? 10.0);
        $isAdmis = ($gpaApr >= $passingThreshold) && !$hasElimination;

        return [
            'gpa_avr' => $gpaAvr,
            'gpa_apr' => $gpaApr,
            'is_admis' => $isAdmis,
            'has_elimination' => $hasElimination
        ];
    }

    /**
     * Calculate final graduation GPA.
     */
    public function calculateGraduationGpa(array $semestersGpa, ?float $noteMemoire, ?float $noteSoutenance, bool $isTS, array $config): array
    {
        if (empty($semestersGpa)) {
            return [
                'gpa' => 0.0,
                'is_admis' => false
            ];
        }

        if ($isTS) {
            $semAverage = array_sum($semestersGpa) / count($semestersGpa);
            
            $thesisWeight = (float)($config['graduation']['ts_degree']['thesis_weight'] ?? 1.0);
            $semAverageWeight = (float)($config['graduation']['ts_degree']['semester_average_weight'] ?? 2.0);
            $divisor = (float)($config['graduation']['ts_degree']['divisor'] ?? 3.0);
            
            $thesisAvg = 0.0;
            if ($noteMemoire !== null && $noteSoutenance !== null) {
                $thesisAvg = ($noteMemoire + $noteSoutenance) / 2.0;
            } elseif ($noteMemoire !== null) {
                $thesisAvg = $noteMemoire;
            } elseif ($noteSoutenance !== null) {
                $thesisAvg = $noteSoutenance;
            }
            
            if ($divisor > 0) {
                $gpa = ($semAverage * $semAverageWeight + $thesisAvg * $thesisWeight) / $divisor;
            } else {
                $gpa = $semAverage;
            }
        } else {
            $gpa = array_sum($semestersGpa) / count($semestersGpa);
        }

        $gpa = round($gpa * 100) / 100;
        $passingThreshold = (float)($config['graduation']['passing_gpa_threshold'] ?? 10.0);

        return [
            'gpa' => $gpa,
            'is_admis' => ($gpa >= $passingThreshold)
        ];
    }
}

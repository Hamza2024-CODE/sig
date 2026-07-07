<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Domains\Academic\Services\GradingSystemService;
use App\Helpers\GradingConfigHelper;

class GradingSystemServiceTest extends TestCase
{
    private GradingSystemService $service;
    private array $defaultConfig;

    protected function setUp(): void
    {
        $this->service = new GradingSystemService();
        $this->defaultConfig = [
            'module_grade' => [
                'continuous_assessment_weight' => 0.4,
                'quiz_weight'                  => 0.4,
                'exam_weight'                  => 0.6,
                'divisor'                      => 1.0,
            ],
            'remedial' => [
                'passing_threshold' => 10.0,
            ],
            'semester' => [
                'passing_gpa_threshold' => 10.0,
                'elimination_threshold' => 5.0,
                'apprenticeship' => [
                    'company_coefficient' => 4.0,
                ]
            ],
            'graduation' => [
                'passing_gpa_threshold' => 10.0,
                'ts_degree' => [
                    'semester_average_weight' => 2.0,
                    'thesis_weight'           => 1.0,
                    'divisor'                 => 3.0,
                ]
            ],
            'workflow' => [
                'grading_start_date' => '2026-06-01',
                'grading_end_date'   => '2026-06-30',
                'remedial_allowed_establishments' => [],
                'final_validation_active' => false,
            ]
        ];
    }

    public function testCalculateModuleGradeTheoriqueWithoutRattrapage()
    {
        $grades = [
            'type_matiere' => 'theorique',
            'cc1' => 12.0,
            'cc2' => 14.0,
            'exam' => 10.0,
        ];
        
        $result = $this->service->calculateModuleGrade($grades, $this->defaultConfig);
        
        // ccAvg = (12 + 14)/2 = 13.0
        // moy_avr = (13.0 * 0.4) + (10.0 * 0.6) = 5.2 + 6.0 = 11.20
        $this->assertEquals(11.20, $result['moy_avr']);
        $this->assertEquals(11.20, $result['moy_apr']);
        $this->assertFalse($result['is_eliminated']);
    }

    public function testCalculateModuleGradeTheoriqueWithRattrapage()
    {
        $grades = [
            'type_matiere' => 'theorique',
            'cc1' => 8.0,
            'cc2' => 8.0,
            'exam' => 6.0,
            'rattrapage' => 12.0
        ];
        
        $result = $this->service->calculateModuleGrade($grades, $this->defaultConfig);
        
        // ccAvg = 8.0
        // moy_avr = (8.0 * 0.4) + (6.0 * 0.6) = 3.2 + 3.6 = 6.80
        // moy_apr = (8.0 * 0.4) + (max(6, 12) * 0.6) = 3.2 + 7.2 = 10.40
        $this->assertEquals(6.80, $result['moy_avr']);
        $this->assertEquals(10.40, $result['moy_apr']);
        $this->assertFalse($result['is_eliminated']);
    }

    public function testCalculateModuleGradeStagePratique()
    {
        $grades = [
            'type_matiere' => 'stage_pratique',
            'stage' => 14.5
        ];
        
        $result = $this->service->calculateModuleGrade($grades, $this->defaultConfig);
        
        $this->assertEquals(14.5, $result['moy_avr']);
        $this->assertEquals(14.5, $result['moy_apr']);
        $this->assertFalse($result['is_eliminated']);
        
        // Test elimination
        $gradesElim = [
            'type_matiere' => 'stage_pratique',
            'stage' => 4.5
        ];
        $resultElim = $this->service->calculateModuleGrade($gradesElim, $this->defaultConfig);
        $this->assertTrue($resultElim['is_eliminated']);
    }

    public function testCalculateModuleGradeMemoire()
    {
        $grades = [
            'type_matiere' => 'memoire',
            'memoire' => 12.0,
            'soutenance' => 15.0
        ];
        
        $result = $this->service->calculateModuleGrade($grades, $this->defaultConfig);
        
        // avg = 12 * 0.6 + 15 * 0.4 = 7.2 + 6.0 = 13.20
        $this->assertEquals(13.20, $result['moy_avr']);
        $this->assertFalse($result['is_eliminated']);
    }

    public function testCalculateSemesterGpaPresentiel()
    {
        $modules = [
            ['coefficient' => 2, 'note_avr' => 12.0, 'note_apr' => 12.0],
            ['coefficient' => 3, 'note_avr' => 9.0, 'note_apr' => 11.0],
        ];
        
        $result = $this->service->calculateSemesterGpa($modules, null, 'presentiel', $this->defaultConfig);
        
        // totalCoef = 5
        // sumAvr = 12 * 2 + 9 * 3 = 24 + 27 = 51 / 5 = 10.2
        // sumApr = 12 * 2 + 11 * 3 = 24 + 33 = 57 / 5 = 11.4
        $this->assertEquals(10.2, $result['gpa_avr']);
        $this->assertEquals(11.4, $result['gpa_apr']);
        $this->assertTrue($result['is_admis']);
    }

    public function testCalculateSemesterGpaApprentissage()
    {
        $modules = [
            ['coefficient' => 2, 'note_avr' => 12.0, 'note_apr' => 12.0],
            ['coefficient' => 3, 'note_avr' => 8.0, 'note_apr' => 8.0],
        ];
        
        $result = $this->service->calculateSemesterGpa($modules, 14.0, 'apprentissage', $this->defaultConfig);
        
        // totalCoef = 2 + 3 + 4 (companyCoef) = 9
        // sumApr = 12*2 + 8*3 + 14*4 = 24 + 24 + 56 = 104 / 9 = 11.56
        $this->assertEquals(11.56, $result['gpa_apr']);
        $this->assertTrue($result['is_admis']);
    }

    public function testCalculateGraduationGpaStandard()
    {
        $semestersGpa = [12.5, 13.0, 11.5, 14.0];
        
        $result = $this->service->calculateGraduationGpa($semestersGpa, null, null, false, $this->defaultConfig);
        
        // average = (12.5 + 13 + 11.5 + 14) / 4 = 51 / 4 = 12.75
        $this->assertEquals(12.75, $result['gpa']);
        $this->assertTrue($result['is_admis']);
    }

    public function testCalculateGraduationGpaTsDegree()
    {
        $semestersGpa = [12.0, 12.0, 12.0, 12.0];
        
        $result = $this->service->calculateGraduationGpa($semestersGpa, 15.0, 15.0, true, $this->defaultConfig);
        
        // average sem = 12.0
        // thesisAvg = 15.0
        // gpa = (12 * 2 + 15 * 1) / 3 = 39 / 3 = 13.0
        $this->assertEquals(13.0, $result['gpa']);
        $this->assertTrue($result['is_admis']);
    }

    public function testSecurityCastingAndConfigSanitization()
    {
        // Test type-casting / sanitization behavior
        $dirtyConfig = [
            'module_grade' => [
                'continuous_assessment_weight' => '0.45 text injection',
                'quiz_weight'                  => '<script>alert(1)</script> 0.35',
                'exam_weight'                  => 0.6,
                'divisor'                      => '1.5',
            ],
            'workflow' => [
                'grading_start_date' => '2026-06-01; exec(something)',
                'grading_end_date'   => '2026-06-30',
                'remedial_allowed_establishments' => ['1', '2.5', 'abc'],
                'final_validation_active' => '1',
            ]
        ];

        // Read original config to restore later
        $originalConfig = GradingConfigHelper::read();

        // Write dirty config
        $writeResult = GradingConfigHelper::write($dirtyConfig);
        $this->assertTrue($writeResult);

        // Read it back and check casting
        $cleanConfig = GradingConfigHelper::read();

        // Check values are fully sanitized and cast correctly
        $this->assertSame(0.45, $cleanConfig['module_grade']['continuous_assessment_weight']);
        $this->assertSame(0.0, $cleanConfig['module_grade']['quiz_weight']); // Fallback of float casting "<script>..." is 0
        $this->assertSame(1.5, $cleanConfig['module_grade']['divisor']);
        
        // Date regex should fail for '2026-06-01; exec(something)' and fallback to current date Y-m-d
        $this->assertNotSame('2026-06-01; exec(something)', $cleanConfig['workflow']['grading_start_date']);
        $this->assertTrue(preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleanConfig['workflow']['grading_start_date']) === 1);
        
        $this->assertSame([1, 2, 0], $cleanConfig['workflow']['remedial_allowed_establishments']);
        $this->assertTrue($cleanConfig['workflow']['final_validation_active']);

        // Restore original config
        GradingConfigHelper::write($originalConfig);
    }
}

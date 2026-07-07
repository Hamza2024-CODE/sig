<?php

// Standalone test runner for SIG MFEP Grading Engine
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Domains/Academic/Services/GradingSystemService.php';
require_once BASE_PATH . '/app/Helpers/GradingConfigHelper.php';

use App\Domains\Academic\Services\GradingSystemService;
use App\Helpers\GradingConfigHelper;

class StandaloneTestRunner {
    private GradingSystemService $service;
    private array $defaultConfig;

    public function __construct() {
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

    public function run() {
        $methods = get_class_methods($this);
        $passed = 0;
        $failed = 0;

        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                try {
                    $this->$method();
                    echo "✓ {$method} PASSED\n";
                    $passed++;
                } catch (\Exception $e) {
                    echo "✗ {$method} FAILED: " . $e->getMessage() . "\n";
                    $failed++;
                }
            }
        }

        echo "\nTotal Tests: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
        exit($failed > 0 ? 1 : 0);
    }

    private function assertEquals($expected, $actual, $msg = '') {
        if ($expected !== $actual) {
            throw new \Exception("Expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ". {$msg}");
        }
    }

    private function assertTrue($val, $msg = '') {
        if ($val !== true) {
            throw new \Exception("Expected true, got " . var_export($val, true) . ". {$msg}");
        }
    }

    private function assertFalse($val, $msg = '') {
        if ($val !== false) {
            throw new \Exception("Expected false, got " . var_export($val, true) . ". {$msg}");
        }
    }

    private function assertNotSame($expected, $actual, $msg = '') {
        if ($expected === $actual) {
            throw new \Exception("Expected not same, but both are " . var_export($expected, true) . ". {$msg}");
        }
    }

    private function assertSame($expected, $actual, $msg = '') {
        if ($expected !== $actual) {
            throw new \Exception("Expected same " . var_export($expected, true) . ", got " . var_export($actual, true) . ". {$msg}");
        }
    }

    // --- TEST CASES ---

    public function testCalculateModuleGradeTheoriqueWithoutRattrapage() {
        $grades = [
            'type_matiere' => 'theorique',
            'cc1' => 12.0,
            'cc2' => 14.0,
            'exam' => 10.0,
        ];
        
        $result = $this->service->calculateModuleGrade($grades, $this->defaultConfig);
        $this->assertEquals(11.20, $result['moy_avr']);
        $this->assertEquals(11.20, $result['moy_apr']);
        $this->assertFalse($result['is_eliminated']);
    }

    public function testCalculateModuleGradeTheoriqueWithRattrapage() {
        $grades = [
            'type_matiere' => 'theorique',
            'cc1' => 8.0,
            'cc2' => 8.0,
            'exam' => 6.0,
            'rattrapage' => 12.0
        ];
        
        $result = $this->service->calculateModuleGrade($grades, $this->defaultConfig);
        $this->assertEquals(6.80, $result['moy_avr']);
        $this->assertEquals(10.40, $result['moy_apr']);
        $this->assertFalse($result['is_eliminated']);
    }

    public function testCalculateModuleGradeStagePratique() {
        $grades = [
            'type_matiere' => 'stage_pratique',
            'stage' => 14.5
        ];
        
        $result = $this->service->calculateModuleGrade($grades, $this->defaultConfig);
        $this->assertEquals(14.5, $result['moy_avr']);
        $this->assertEquals(14.5, $result['moy_apr']);
        $this->assertFalse($result['is_eliminated']);
        
        $gradesElim = [
            'type_matiere' => 'stage_pratique',
            'stage' => 4.5
        ];
        $resultElim = $this->service->calculateModuleGrade($gradesElim, $this->defaultConfig);
        $this->assertTrue($resultElim['is_eliminated']);
    }

    public function testCalculateModuleGradeMemoire() {
        $grades = [
            'type_matiere' => 'memoire',
            'memoire' => 12.0,
            'soutenance' => 15.0
        ];
        
        $result = $this->service->calculateModuleGrade($grades, $this->defaultConfig);
        $this->assertEquals(13.20, $result['moy_avr']);
        $this->assertFalse($result['is_eliminated']);
    }

    public function testCalculateSemesterGpaPresentiel() {
        $modules = [
            ['coefficient' => 2, 'note_avr' => 12.0, 'note_apr' => 12.0],
            ['coefficient' => 3, 'note_avr' => 9.0, 'note_apr' => 11.0],
        ];
        
        $result = $this->service->calculateSemesterGpa($modules, null, 'presentiel', $this->defaultConfig);
        $this->assertEquals(10.2, $result['gpa_avr']);
        $this->assertEquals(11.4, $result['gpa_apr']);
        $this->assertTrue($result['is_admis']);
    }

    public function testCalculateSemesterGpaApprentissage() {
        $modules = [
            ['coefficient' => 2, 'note_avr' => 12.0, 'note_apr' => 12.0],
            ['coefficient' => 3, 'note_avr' => 8.0, 'note_apr' => 8.0],
        ];
        
        $result = $this->service->calculateSemesterGpa($modules, 14.0, 'apprentissage', $this->defaultConfig);
        $this->assertEquals(11.56, $result['gpa_apr']);
        $this->assertTrue($result['is_admis']);
    }

    public function testCalculateGraduationGpaStandard() {
        $semestersGpa = [12.5, 13.0, 11.5, 14.0];
        
        $result = $this->service->calculateGraduationGpa($semestersGpa, null, null, false, $this->defaultConfig);
        $this->assertEquals(12.75, $result['gpa']);
        $this->assertTrue($result['is_admis']);
    }

    public function testCalculateGraduationGpaTsDegree() {
        $semestersGpa = [12.0, 12.0, 12.0, 12.0];
        
        $result = $this->service->calculateGraduationGpa($semestersGpa, 15.0, 15.0, true, $this->defaultConfig);
        $this->assertEquals(13.0, $result['gpa']);
        $this->assertTrue($result['is_admis']);
    }

    public function testSecurityCastingAndConfigSanitization() {
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

        $originalConfig = GradingConfigHelper::read();
        $writeResult = GradingConfigHelper::write($dirtyConfig);
        $this->assertTrue($writeResult);

        $cleanConfig = GradingConfigHelper::read();

        $this->assertSame(0.45, $cleanConfig['module_grade']['continuous_assessment_weight']);
        $this->assertSame(0.0, $cleanConfig['module_grade']['quiz_weight']);
        $this->assertSame(1.5, $cleanConfig['module_grade']['divisor']);
        
        $this->assertNotSame('2026-06-01; exec(something)', $cleanConfig['workflow']['grading_start_date']);
        $this->assertTrue(preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleanConfig['workflow']['grading_start_date']) === 1);
        
        $this->assertSame([1, 2, 0], $cleanConfig['workflow']['remedial_allowed_establishments']);
        $this->assertTrue($cleanConfig['workflow']['final_validation_active']);

        GradingConfigHelper::write($originalConfig);
    }
}

$runner = new StandaloneTestRunner();
$runner->run();

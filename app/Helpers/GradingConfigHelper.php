<?php

namespace App\Helpers;

class GradingConfigHelper
{
    private const FILE_PATH = BASE_PATH . '/config/grading.php';

    private const SCHEMA = [
        'module_grade' => [
            'continuous_assessment_weight' => 'float',
            'quiz_weight'                  => 'float',
            'exam_weight'                  => 'float',
            'divisor'                      => 'float',
        ],
        'remedial' => [
            'passing_threshold' => 'float',
        ],
        'semester' => [
            'passing_gpa_threshold' => 'float',
            'elimination_threshold' => 'float',
            'apprenticeship' => [
                'company_coefficient' => 'float',
            ]
        ],
        'distance_learning' => [
            'weights' => [
                'written_exam'      => 'float',
                'attendance'        => 'float',
                'platform_activity' => 'float',
                'oral_exam'         => 'float',
                'assignments'       => 'float',
            ]
        ],
        'graduation' => [
            'passing_gpa_threshold' => 'float',
            'ts_degree' => [
                'semester_average_weight' => 'float',
                'thesis_weight'           => 'float',
                'divisor'                 => 'float',
            ]
        ],
        'workflow' => [
            'grading_start_date' => 'date',
            'grading_end_date'   => 'date',
            'remedial_allowed_establishments' => 'array_int',
            'final_validation_active' => 'bool',
        ],
        'modes' => 'array'
    ];

    /**
     * Read configuration array from file.
     */
    public static function read(): array
    {
        if (file_exists(self::FILE_PATH)) {
            $config = include self::FILE_PATH;
            if (is_array($config)) {
                return $config;
            }
        }

        // Fallback default config if file is missing or invalid
        return [
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
            'distance_learning' => [
                'weights' => [
                    'written_exam'      => 1.0,
                    'attendance'        => 0.0,
                    'platform_activity' => 0.0,
                    'oral_exam'         => 0.0,
                    'assignments'       => 0.0,
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

    /**
     * Write new configuration safely after casting/sanitizing against SCHEMA.
     */
    public static function write(array $newConfig): bool
    {
        $sanitized = self::sanitize($newConfig, self::SCHEMA);
        
        $content = "<?php\n\nreturn " . var_export($sanitized, true) . ";\n";
        
        $tempFile = self::FILE_PATH . '.tmp';
        if (file_put_contents($tempFile, $content) !== false) {
            if (rename($tempFile, self::FILE_PATH)) {
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate(self::FILE_PATH, true);
                }
                return true;
            }
        }
        
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        return false;
    }

    /**
     * Recursively match input array against schema and enforce strict casting.
     */
    private static function sanitize(array $input, array $schema): array
    {
        $output = [];
        foreach ($schema as $key => $type) {
            if ($key === 'modes' && isset($input['modes']) && is_array($input['modes'])) {
                // Special handling for dynamic modes array
                $output['modes'] = [];
                foreach ($input['modes'] as $modeId => $modeData) {
                    if (is_array($modeData)) {
                        $sanitizedMode = [];
                        foreach ($modeData as $mKey => $mVal) {
                            // Only allow known keys to be floats
                            $sanitizedMode[$mKey] = (float)$mVal;
                        }
                        $output['modes'][(int)$modeId] = $sanitizedMode;
                    }
                }
                continue;
            }

            if (!isset($input[$key])) {
                // If it is sub-schema, sanitize empty array
                if (is_array($type)) {
                    $output[$key] = self::sanitize([], $type);
                } else {
                    if ($key === 'modes') {
                        $output['modes'] = [];
                    } else {
                        $output[$key] = self::defaultValueForType($type);
                    }
                }
                continue;
            }

            $value = $input[$key];

            if (is_array($type)) {
                $output[$key] = self::sanitize(is_array($value) ? $value : [], $type);
            } else {
                $output[$key] = self::castValue($value, $type);
            }
        }
        return $output;
    }

    /**
     * Cast values to expected type safely.
     */
    private static function castValue($value, string $type)
    {
        switch ($type) {
            case 'float':
                return (float)$value;
            case 'int':
                return (int)$value;
            case 'bool':
                return (bool)$value;
            case 'date':
                // Enforce YYYY-MM-DD pattern. If not matched, fallback to default date.
                $str = trim((string)$value);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
                    return $str;
                }
                return date('Y-m-d');
            case 'array_int':
                if (!is_array($value)) {
                    return [];
                }
                $arr = [];
                foreach ($value as $item) {
                    $arr[] = (int)$item;
                }
                return $arr;
            default:
                return null;
        }
    }

    /**
     * Provide safe defaults if input key is missing.
     */
    private static function defaultValueForType(string $type)
    {
        switch ($type) {
            case 'float':
                return 0.0;
            case 'int':
                return 0;
            case 'bool':
                return false;
            case 'date':
                return date('Y-m-d');
            case 'array_int':
                return [];
            default:
                return null;
        }
    }
}

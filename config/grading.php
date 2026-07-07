<?php

return array (
  'module_grade' => 
  array (
    'continuous_assessment_weight' => 0.4,
    'quiz_weight' => 0.4,
    'exam_weight' => 0.6,
    'divisor' => 1.0,
  ),
  'remedial' => 
  array (
    'passing_threshold' => 10.0,
  ),
  'semester' => 
  array (
    'passing_gpa_threshold' => 10.0,
    'elimination_threshold' => 5.0,
    'apprenticeship' => 
    array (
      'company_coefficient' => 4.0,
    ),
  ),
  'distance_learning' => 
  array (
    'weights' => 
    array (
      'written_exam' => 1.0,
      'attendance' => 0.0,
      'platform_activity' => 0.0,
      'oral_exam' => 0.0,
      'assignments' => 0.0,
    ),
  ),
  'graduation' => 
  array (
    'passing_gpa_threshold' => 10.0,
    'ts_degree' => 
    array (
      'semester_average_weight' => 2.0,
      'thesis_weight' => 1.0,
      'divisor' => 3.0,
    ),
  ),
  'workflow' => 
  array (
    'grading_start_date' => '2026-06-01',
    'grading_end_date' => '2026-06-30',
    'remedial_allowed_establishments' => 
    array (
    ),
    'final_validation_active' => false,
  ),
  'modes' => 
  array (
  ),
);

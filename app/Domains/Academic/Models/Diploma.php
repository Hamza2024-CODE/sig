<?php

namespace App\Domains\Academic\Models;

class Diploma
{
    public int $id;
    public int $traineeId;
    public ?int $apprenantSectionSemesterId;
    public string $diplomaNum;
    public float $averageGrade;
    public string $dateIssued;
    public int $mentionId;
    public ?int $sectionSemesterId;

    public function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->traineeId = (int)($data['trainee_id'] ?? 0);
        $this->apprenantSectionSemesterId = isset($data['apprenant_section_semstre_id']) ? (int)$data['apprenant_section_semstre_id'] : null;
        $this->diplomaNum = (string)($data['diploma_num'] ?? '');
        $this->averageGrade = (float)($data['average_grade'] ?? 0.0);
        $this->dateIssued = (string)($data['date_issued'] ?? date('Y-m-d'));
        $this->mentionId = (int)($data['mention_id'] ?? 1);
        $this->sectionSemesterId = isset($data['section_semestre_id']) ? (int)$data['section_semestre_id'] : null;
    }
}

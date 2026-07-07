<?php

namespace App\Domains\Finance\Services;

use App\Domains\Finance\Repositories\FinanceRepository;
use App\Helpers\EncryptionHelper;

class BudgetService
{
    protected FinanceRepository $repo;

    public function __construct(FinanceRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Get the financial budget report data.
     */
    public function getBudgetReportData(): array
    {
        $boursesSummary = $this->repo->getBoursesSummary();
        $budgetsList = $this->repo->getBudgetsList();

        return [
            'generated_at'    => date('Y-m-d H:i:s'),
            'bourses_summary' => $boursesSummary,
            'budgets'         => $budgetsList
        ];
    }

    /**
     * Generate the encrypted budget payload using AES-256-CBC via EncryptionHelper.
     */
    public function getEncryptedBudgetReport(): string
    {
        $data = $this->getBudgetReportData();
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Encrypt the JSON payload using application-level AES-256-CBC
        return EncryptionHelper::encrypt($json);
    }
}

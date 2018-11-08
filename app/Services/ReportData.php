<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\Paginator;

/**
 * Данные для отчета.
 *
 * @package App\Services
 */
class ReportData
{
    /**
     * Список операций.
     *
     * @var Collection
     */
    private $operations;

    /**
     * Сумма операций в валюте кошелька.
     *
     * @var float
     */
    private $sum;

    /**
     * Сумма операций в USD.
     *
     * @var float
     */
    private $sumUsd;

    /**
     * ReportData constructor.
     *
     * @param Collection|Paginator $operations
     * @param string $sum
     * @param string $sumUsd
     */
    public function __construct($operations, string $sum, string $sumUsd)
    {
        $this->operations = $operations;
        $this->sum = $sum;
        $this->sumUsd = $sumUsd;
    }

    /**
     * @return string
     */
    public function getSum(): string
    {
        return $this->sum;
    }

    /**
     * @return string
     */
    public function getSumUsd(): string
    {
        return $this->sumUsd;
    }

    /**
     * @return Paginator|Collection
     */
    public function getOperations()
    {
        return $this->operations;
    }
}

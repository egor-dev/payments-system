<?php

namespace App\Services;

use App\Account;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

/**
 * Предоставляет данные для отчёта.
 *
 * @package App\Services
 */
class ReportDataProvider
{
    /**
     * @var Builder
     */
    private $rowsQuery;

    /**
     * @var Builder
     */
    private $sumQuery;

    /**
     * ReportDataProvider constructor.
     *
     * @param Account $account
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @throws \Exception
     */
    public function __construct(Account $account, ?Carbon $start, ?Carbon $end)
    {
        $this->initQueries($account, $start, $end);
    }

    /**
     * Получить данные для web-отчёта.
     *
     * @param int $perPage
     *
     * @return ReportData
     */
    public function paginatedData(int $perPage): ReportData
    {
        return new ReportData(
            $this->rowsQuery->orderBy('id', 'desc')->simplePaginate($perPage),
            $this->sumQuery->sum('sum'),
            $this->sumQuery->sum('sum_usd')
        );
    }

    /**
     * Получить данные для экспорта.
     *
     * @return ReportData
     */
    public function getExportData(): ReportData
    {
        return new ReportData(
            $this->rowsQuery->orderBy('id', 'asc')->get(),
            $this->sumQuery->sum('sum'),
            $this->sumQuery->sum('sum_usd')
        );
    }

    /**
     * @param Account $account
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @throws \Exception
     */
    private function initQueries(Account $account, ?Carbon $start, ?Carbon $end): void
    {
        $this->rowsQuery = $account->operations()
            ->with('transaction', 'transaction.transactionType')
            ->select('id', 'account_id', 'transaction_id', 'amount', 'created_at');
        $this->sumQuery = $account->operationsAggregations();

        if (null !== $start) {
            $this->rowsQuery->where('created_at', '>=', $start);
            $this->sumQuery->where('date', '>=', $start->startOfDay());
        }
        if (null !== $end) {
            $this->rowsQuery->where('created_at', '<=', $end);
            $this->sumQuery->where('date', '<=', $end->endOfDay());
        }
    }
}

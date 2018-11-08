<?php

namespace App\Exports;

use App\Account;
use App\Operation;
use Carbon\Carbon;
use App\Services\ReportData;
use Illuminate\Support\Collection;
use App\Services\ReportDataProvider;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Экспорт отчета по операциям.
 *
 * @package App\Exports
 */
class OperationExport implements WithHeadings, WithMapping, FromCollection, WithEvents
{
    use Exportable;

    /**
     * @var int
     */
    private $account;

    /**
     * @var ReportData
     */
    private $reportData;

    /**
     * OperationExport constructor.
     *
     * @param Account $account
     * @param Carbon|null $dateFrom
     * @param Carbon|null $dateTo
     *
     * @throws \Exception
     */
    public function __construct(Account $account, ?Carbon $dateFrom, ?Carbon $dateTo)
    {
        $this->account = $account;

        $this->reportData = (new ReportDataProvider($account, $dateFrom, $dateTo))->getExportData();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Operation ID', 'Transaction ID', 'Amount', 'Description', 'Date',
        ];
    }

    /**
     * @param Operation $operation
     *
     * @return array
     */
    public function map($operation): array
    {
        return [
            $operation->id,
            $operation->transaction_id,
            $operation->amount,
            $operation->description,
            $operation->created_at->toDayDateTimeString(),
        ];
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->reportData->getOperations();
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        $currencyIso = mb_strtoupper($this->account->currency->iso);
        $currencySign = $this->account->currency->sign;

        $usdCurrencyIso = mb_strtoupper('usd');

        $append = 'Operations sum: ' . money_output($this->reportData->getSum(), $currencySign);

        if ($currencyIso !== $usdCurrencyIso) {
            $append .= ' (' . money_output($this->reportData->getSumUsd(), '$)');
        }

        return [
            AfterSheet::class => function (AfterSheet $event) use ($append) {
                $event->getSheet()->append([$append]);
            },
        ];
    }
}

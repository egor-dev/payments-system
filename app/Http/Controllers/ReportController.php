<?php

namespace App\Http\Controllers;

use App\User;
use App\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use App\Exports\OperationExport;
use App\Services\ReportDataProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Контроллер отчета.
 *
 * @package App\Http\Controllers
 */
class ReportController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ]);

        $userName = $request->input('user_name');
        if (null === $userName) {
            return view('report');
        }

        /** @var User $user */
        $user = User::with('account')->whereName($userName)->first();
        if (null === $user) {
            flash()->error("There is no client with name <strong>{$userName}</strong>.");

            return redirect()->back()->withInput();
        }

        $account = $user->account;
        $dateFrom = $request->filled('date_from') ? Carbon::createFromFormat('Y-m-d', $request->input('date_from')) : null;
        $dateTo = $request->filled('date_to') ? Carbon::createFromFormat('Y-m-d', $request->input('date_to')) : null;
        if ($dateFrom && $dateTo && $dateTo->lt($dateFrom)) {
            flash()->error('Start date can not be greater than end date.');

            return redirect()->back()->withInput();
        }

        $dataProvider = new ReportDataProvider($account, $dateFrom, $dateTo);

        $data = $dataProvider->paginatedData(self::PER_PAGE);
        $operations = $data->getOperations();
        $sum = $data->getSum();
        $sumUsd = $data->getSumUsd();

        session()->flash('user_name', $userName);
        session()->flash('date_from', $dateFrom);
        session()->flash('date_to', $dateTo);

        return view('report', compact('account', 'operations', 'sum', 'sumUsd'));
    }

    /**
     * @param Excel $excel
     * @param Request $request
     * @param Account $account
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \Exception
     *
     * @return BinaryFileResponse
     */
    public function export(Excel $excel, Request $request, Account $account): BinaryFileResponse
    {
        $validatedData = $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ]);

        $dateFrom = isset($validatedData['date_from']) ?
            Carbon::createFromFormat('Y-m-d', $validatedData['date_from'])->startOfDay() :
            null;
        $dateTo = isset($validatedData['date_to']) ?
            Carbon::createFromFormat('Y-m-d', $validatedData['date_to'])->endOfDay() :
            null;

        $export = new OperationExport($account, $dateFrom, $dateTo);

        return $excel->download($export, 'operations.csv');
    }
}

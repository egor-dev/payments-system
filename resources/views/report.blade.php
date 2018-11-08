@extends('layout')

@section('title', 'Report')

@section('content')

    <h2 class="mb-4"><a href="/">Report</a></h2>

    {!! Form::open(['url' => '/', 'method'=>'GET']) !!}
    <div class="card">
        <div class="card-body bg-light">
            <div class="row">
                <div class="col-md-4">
                    <label for="client_name">Client name:</label>
                    {!! Form::input('text', 'user_name', session()->get('user_name'), ['class'=>'form-control', 'placeholder'=> 'Enter client name']) !!}
                </div>
                <div class="col-md-3">
                    <label for="date_from">Start date:</label>
                    {!! Form::date('date_from', session()->get('date_from'), ['class'=>'form-control']) !!}
                </div>
                <div class="col-md-3">
                    <label for="date_to">End date:</label>
                    {!! Form::date('date_to', session()->get('date_to'), ['class'=>'form-control']) !!}
                </div>
                <div class="col-md-2 flex-column align-bottom">
                    <label for="date_to">&nbsp;</label>
                    {!! Form::submit('Show', ['class'=> 'form-control btn btn-success']) !!}
                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}

    @if(isset($operations) && count($operations))
        <div class="mb-4 mt-4">
            <a href="/export/{{ $account->id }}" role="button"
               class="btn btn-outline-primary">Export to CSV</a>
        </div>
        <div class="my-xl-5">
            <div class="mb-4">
                {{ $operations->appends([
                    'user_name' => request()->input('user_name'),
                    'date_from' => request()->input('date_from'),
                    'date_to' => request()->input('date_to'),
                ])->links() }}
            </div>

            <table class="table table-striped mb-4 small">
                <thead>
                <tr>
                    <th>Operation ID</th>
                    <th>Transaction ID</th>
                    <th class="text-right">Amount</th>
                    <th>Description</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                @foreach($operations as $operation)
                    <tr>
                        <td>{{ $operation->id }}</td>
                        <td>{{ $operation->transaction_id }}</td>
                        <td class="text-right">{{ money_output($operation->amount, $operation->account->currency->sign) }} </td>
                        <td>
                            <span class="text-{{ $operation->isIncoming() ? 'success' : 'danger' }}">{{ $operation->description }}</span>
                        </td>
                        <td>{{ $operation->created_at->toDayDateTimeString() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <p>
                Operations sum: <strong>{{ money_output($sum, $account->currency->sign) }}</strong>

                @if($account->currency->iso !== 'usd')
                    <small>({{ money_output($sumUsd, '$') }})</small>
                @endif
            </p>

            <div class="mb-4">
                {{ $operations->appends([
                    'user_name' => request()->input('user_name'),
                    'date_from' => request()->input('date_from'),
                    'date_to' => request()->input('date_to'),
                ])->links() }}
            </div>

        </div>
    @else
        <p class="my-5">Nothing.</p>
    @endif

    {{--блок добавлен лишь с целью удобства демонстрации, не нужно осуждать за то, что тут делаются запросы :) --}}

        <div class="d-flex flex-row mb-5">
            @if(count($users = \App\User::with('account', 'account.currency')->get()))
            <div class="small w-25">
                <h6>Clients:</h6>
                @foreach($users as $user)
                    <div>#{{ $user->id }} <a href="/?user_name={{ $user->name }}">{{ $user->name }}</a> {{ $user->account->currency->sign }}</div>
                @endforeach
            </div>
            @endif
            @if(count($exchangeRates = \App\ExchangeRate::with('fixedCurrency', 'variableCurrency')->get()))
                <div class="small w-25">
                    <h6>Exchange rates:</h6>
                    <table class="table w-auto">
                        @foreach($exchangeRates as $rate)
                            <tr>
                                <td>{{ $rate->fixedCurrency->iso }} / {{ $rate->variableCurrency->iso }}</td>
                                <td>{{ $rate->rate }}</td>
                                <td>{{ $rate->date }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
            @if(count($currencies = \App\Currency::all()))
                <div class="small w-25">
                    <h6>Currencies:</h6>
                    <table class="table w-auto">
                        @foreach($currencies as $currency)
                            <tr>
                                <td>{{ $currency->name }}</td>
                                <td>{{ mb_strtoupper($currency->iso) }}</td>
                                <td>{{ $currency->sign }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>

@endsection
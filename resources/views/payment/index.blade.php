<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        {!! $button !!}
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('message.rider') }}</th>
                                        <th>{{ __('message.driver') }}</th>
                                        <th>{{ __('message.total') ?? 'Total' }}</th>
                                        <th>{{ __('message.currency') ?? 'Currency' }}</th>
                                        <th>{{ __('message.payment_method') }}</th>
                                        <th>{{ __('message.payment_status_message') ?? 'Status' }}</th>
                                        <th>{{ __('message.date') ?? 'Date' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($payments))
                                        @foreach($payments as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['rider_uid'] ?? '-' }}</td>
                                                <td>{{ $row['driver_uid'] ?? '-' }}</td>
                                                <td>{{ $row['total'] ?? '-' }}</td>
                                                <td>{{ $row['currency'] ?? '-' }}</td>
                                                <td>{{ $row['payment_method'] ?? '-' }}</td>
                                                <td>{{ $row['payment_status'] ?? '-' }}</td>
                                                <td>{{ $row['created_at'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="8">لم يتم العثور على سجلات</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

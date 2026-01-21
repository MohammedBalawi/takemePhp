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
                                        <th>{{ __('message.start_address') ?? 'Pickup' }}</th>
                                        <th>{{ __('message.end_address') ?? 'Dropoff' }}</th>
                                        <th>{{ __('message.total') ?? 'Total' }}</th>
                                        <th>{{ __('message.status') }}</th>
                                        <th>{{ __('message.date') ?? 'Date' }}</th>
                                        <th>{{ __('message.payment_method') ?? 'Payment Method' }}</th>
                                        <th>{{ __('message.payment_status_message') ?? 'Payment Status' }}</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rows))
                                        @php
                                            $scalar = function ($value) {
                                                if (is_array($value)) {
                                                    return json_encode($value, JSON_UNESCAPED_UNICODE);
                                                }
                                                if (is_bool($value)) {
                                                    return $value ? '1' : '0';
                                                }
                                                return $value !== null ? (string) $value : '';
                                            };
                                        @endphp
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $scalar($row['rider_name'] ?? ($row['riderName'] ?? ($row['rider_uid'] ?? ($row['riderUid'] ?? '-')))) ?: '-' }}</td>
                                                <td>{{ $scalar($row['driver_name'] ?? ($row['driverName'] ?? ($row['driver_uid'] ?? ($row['driverUid'] ?? '-')))) ?: '-' }}</td>
                                                <td>{{ $scalar($row['pickup_address'] ?? ($row['pickupAddress'] ?? '-')) ?: '-' }}</td>
                                                <td>{{ $scalar($row['dropoff_address'] ?? ($row['dropoffAddress'] ?? '-')) ?: '-' }}</td>
                                                <td>{{ $scalar($row['fare_total'] ?? ($row['fareTotal'] ?? '-')) ?: '-' }}</td>
                                                <td>{{ $scalar($row['status'] ?? '-') ?: '-' }}</td>
                                                <td>{{ $scalar($row['created_at'] ?? ($row['createdAt'] ?? '-')) ?: '-' }}</td>
                                                <td>{{ $scalar($row['payment_method'] ?? ($row['paymentMethod'] ?? '-')) ?: '-' }}</td>
                                                <td>{{ $scalar($row['payment_status'] ?? ($row['paymentStatus'] ?? '-')) ?: '-' }}</td>
                                                <td>
                                                    @if(!empty($row['id']))
                                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('ride.view', ['source' => $row['source'] ?? 'rides', 'id' => $row['id']]) }}">View</a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="10">لم يتم العثور على سجلات</td>
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

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
                                        <th>{{ __('message.code') }}</th>
                                        <th>{{ __('message.title') }}</th>
                                        <th>{{ __('message.coupon_type') }}</th>
                                        <th>{{ __('message.discount_type') }}</th>
                                        <th>{{ __('message.discount') }}</th>
                                        <th>{{ __('message.minimum_amount') }}</th>
                                        <th>{{ __('message.maximum_discount') }}</th>
                                        <th>{{ __('message.start_date') }}</th>
                                        <th>{{ __('message.end_date') }}</th>
                                        <th>{{ __('message.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($coupons))
                                        @foreach($coupons as $coupon)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $coupon['code'] ?? '-' }}</td>
                                                <td>{{ $coupon['title'] ?? '-' }}</td>
                                                <td>{{ $coupon['coupon_type'] ?? '-' }}</td>
                                                <td>{{ $coupon['discount_type'] ?? '-' }}</td>
                                                <td>{{ $coupon['discount'] ?? '-' }}</td>
                                                <td>{{ $coupon['minimum_amount'] ?? '-' }}</td>
                                                <td>{{ $coupon['maximum_discount'] ?? '-' }}</td>
                                                <td>{{ $coupon['start_date'] ?? '-' }}</td>
                                                <td>{{ $coupon['end_date'] ?? '-' }}</td>
                                                <td>
                                                    @php
                                                        $status = $coupon['status'] ?? 'inactive';
                                                    @endphp
                                                    {{ $status === 'active' ? __('message.active') : __('message.inactive') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="11">لا يوجد بيانات</td>
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

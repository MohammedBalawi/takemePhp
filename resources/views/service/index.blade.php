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
                                        <th>الخدمة</th>
                                        <th>الانطلاق</th>
                                        <th>الوجهة</th>
                                        <th>السعر</th>
                                        <th>العملة</th>
                                        <th>الحالة</th>
                                        <th>عدد المتقدمين</th>
                                        <th>المعتمد</th>
                                        <th>التاريخ</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($offers))
                                        @foreach($offers as $offer)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $offer['title'] ?? '-' }}</td>
                                                <td>{{ $offer['pickupAddress'] ?? '-' }}</td>
                                                <td>{{ $offer['dropoffAddress'] ?? '-' }}</td>
                                                <td>{{ $offer['basePrice'] ?? 0 }}</td>
                                                <td>{{ $offer['currency'] ?? '-' }}</td>
                                                <td>{{ $offer['status'] ?? '-' }}</td>
                                                <td>
                                                    @if(!empty($offer['id']))
                                                        <a href="{{ route('service.bidders', $offer['id']) }}">
                                                            {{ $offer['bidsCount'] ?? 0 }}
                                                        </a>
                                                    @else
                                                        {{ $offer['bidsCount'] ?? 0 }}
                                                    @endif
                                                </td>
                                                <td>{{ $offer['assignedDriverUid'] ?? '-' }}</td>
                                                <td>{{ $offer['submittedAt'] ?? '-' }}</td>
                                                <td>
                                                    @if(!empty($offer['id']))
                                                        <a class="btn btn-sm btn-primary" href="{{ route('service.bidders', $offer['id']) }}">
                                                            المتقدمين
                                                        </a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
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

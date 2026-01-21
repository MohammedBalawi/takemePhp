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
                                        <th>النوع</th>
                                        <th>النطاق</th>
                                        <th>المدينة</th>
                                        <th>الخدمة</th>
                                        <th>الموديفاير</th>
                                        <th>اليوم</th>
                                        <th>من - إلى</th>
                                        <th>الحالة</th>
                                        <th>الأولوية</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rules))
                                        @foreach($rules as $rule)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $rule['type'] ?? '-' }}</td>
                                                <td>{{ $rule['scope'] ?? '-' }}</td>
                                                <td>{{ $rule['cityName'] ?? $rule['cityId'] ?? '-' }}</td>
                                                <td>{{ $rule['serviceId'] ?? '-' }}</td>
                                                <td>{{ ($rule['modifierMode'] ?? '') . ' ' . ($rule['modifierValue'] ?? '') }}</td>
                                                <td>{{ $rule['day'] ?? '-' }}</td>
                                                <td>{{ ($rule['startTime'] ?? '-') . ' - ' . ($rule['endTime'] ?? '-') }}</td>
                                                <td>{{ $rule['isActive'] ?? '-' }}</td>
                                                <td>{{ $rule['priority'] ?? '-' }}</td>
                                                <td>{{ $rule['createdAt'] ?? '-' }}</td>
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

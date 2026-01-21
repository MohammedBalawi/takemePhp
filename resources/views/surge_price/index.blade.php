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
                                        <th>المدينة</th>
                                        <th>النوع</th>
                                        <th>الزيادة</th>
                                        <th>الوصف</th>
                                        <th>اليوم</th>
                                        <th>من - إلى</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rules))
                                        @foreach($rules as $rule)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $rule['cityName'] ?? $rule['cityId'] ?? '-' }}</td>
                                                <td>{{ $rule['type'] ?? '-' }}</td>
                                                <td>{{ ($rule['increaseType'] ?? '') . ' ' . ($rule['increaseValue'] ?? '') }}</td>
                                                <td>{{ $rule['description'] ?? '-' }}</td>
                                                <td>{{ $rule['day'] ?? '-' }}</td>
                                                <td>{{ ($rule['timeFrom'] ?? '-') . ' - ' . ($rule['timeTo'] ?? '-') }}</td>
                                                <td>{{ $rule['status'] ?? '-' }}</td>
                                                <td>{{ $rule['createdAt'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="9">لا يوجد بيانات</td>
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

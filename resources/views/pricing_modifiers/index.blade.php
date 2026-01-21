<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{ route('pricing_modifiers.create') }}" class="float-right btn btn-sm border-radius-10 btn-primary me-2">
                            <i class="fa fa-plus-circle"></i> إضافة
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>النوع</th>
                                        <th>المدينة</th>
                                        <th>الخدمة</th>
                                        <th>الموديفاير</th>
                                        <th>اليوم</th>
                                        <th>من-إلى</th>
                                        <th>الحالة</th>
                                        <th>الأولوية</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rows))
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['type'] ?? '-' }}</td>
                                                <td>{{ $row['cityName'] ?? $row['cityId'] ?? '-' }}</td>
                                                <td>{{ $row['serviceId'] ?? '-' }}</td>
                                                <td>{{ ($row['modifierMode'] ?? '') . ' ' . ($row['modifierValue'] ?? '') }}</td>
                                                <td>{{ $row['day'] ?? '-' }}</td>
                                                <td>{{ ($row['startTime'] ?? '-') . ' - ' . ($row['endTime'] ?? '-') }}</td>
                                                <td>{{ $row['isActive'] ?? '-' }}</td>
                                                <td>{{ $row['priority'] ?? '-' }}</td>
                                                <td>{{ $row['createdAt'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="10">لا يوجد بيانات</td>
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

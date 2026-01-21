<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{ route('pricing.create') }}" class="float-right btn btn-sm border-radius-10 btn-primary me-2">
                            <i class="fa fa-plus-circle"></i> إضافة
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>المدينة</th>
                                        <th>الخدمة</th>
                                        <th>العملة</th>
                                        <th>Base</th>
                                        <th>Per KM</th>
                                        <th>Per Min</th>
                                        <th>Min Fare</th>
                                        <th>Active</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rows))
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['cityName'] ?? $row['cityId'] ?? '-' }}</td>
                                                <td>{{ $row['serviceId'] ?? '-' }}</td>
                                                <td>{{ $row['currency'] ?? 'SAR' }}</td>
                                                <td>{{ $row['baseFare'] ?? 0 }}</td>
                                                <td>{{ $row['perKm'] ?? 0 }}</td>
                                                <td>{{ $row['perMin'] ?? 0 }}</td>
                                                <td>{{ $row['minFare'] ?? 0 }}</td>
                                                <td>{{ $row['isActive'] ?? '-' }}</td>
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

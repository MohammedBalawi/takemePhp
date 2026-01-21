<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{ route('monthly.special_needs.create') }}" class="float-right btn btn-sm border-radius-10 btn-primary me-2">
                            <i class="fa fa-plus-circle"></i> إضافة
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الحالة</th>
                                        <th>الاسم</th>
                                        <th>الهاتف</th>
                                        <th>من</th>
                                        <th>إلى</th>
                                        <th>ملاحظات</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rows))
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['status'] ?? '-' }}</td>
                                                <td>{{ $row['name'] ?? '-' }}</td>
                                                <td>{{ $row['phone'] ?? '-' }}</td>
                                                <td>{{ $row['from_address'] ?? '-' }}</td>
                                                <td>{{ $row['to_address'] ?? '-' }}</td>
                                                <td>{{ $row['notes'] ?? '-' }}</td>
                                                <td>{{ $row['created_at'] ?? '-' }}</td>
                                                <td><span class="text-muted">عرض فقط</span></td>
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

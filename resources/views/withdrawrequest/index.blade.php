<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>السائق</th>
                                        <th>المبلغ</th>
                                        <th>الملاحظة</th>
                                        <th>صورة الإيصال</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الطلب</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rows))
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['uid'] ?? '-' }}</td>
                                                <td>{{ $row['amount'] ?? 0 }}</td>
                                                <td>{{ $row['note'] ?? '-' }}</td>
                                                <td>
                                                    @if(!empty($row['receipt_url']))
                                                        <a class="btn btn-sm btn-primary" href="{{ $row['receipt_url'] }}" target="_blank" rel="noopener">
                                                            عرض
                                                        </a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $row['status'] ?? '-' }}</td>
                                                <td>{{ $row['created_at'] ?? '-' }}</td>
                                                <td>
                                                    @if(($row['status'] ?? '') === 'pending')
                                                        <form method="POST" action="{{ route('withdrawrequest.approve', $row['id']) }}" style="display:inline-block">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">موافقة</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('withdrawrequest.decline', $row['id']) }}" style="display:inline-block">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">رفض</button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="8">لا يوجد بيانات</td>
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

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
                                        <th>الاسم</th>
                                        <th>Email</th>
                                        <th>Contact Number</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>العنوان</th>
                                        <th>اسم المستخدم</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($riders))
                                        @foreach($riders as $rider)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                @php
                                                    $fullName = trim(($rider['first_name'] ?? '') . ' ' . ($rider['last_name'] ?? ''));
                                                @endphp
                                                <td>{{ $fullName !== '' ? $fullName : '-' }}</td>
                                                <td>{{ $rider['email'] ?? '-' }}</td>
                                                <td>{{ $rider['phone'] ?? '-' }}</td>
                                                <td>{{ $rider['created_at'] ?? '-' }}</td>
                                                <td>{{ $rider['address'] ?? '-' }}</td>
                                                <td>{{ $rider['username'] ?? '-' }}</td>
                                                <td>
                                                    <span class="text-muted">-</span>
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

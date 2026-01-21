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
                                        <th>{{ __('message.name') }}</th>
                                        <th>{{ __('message.email') }}</th>
                                        <th>{{ __('message.contact_number') }}</th>
                                        <th>{{ __('message.city') }}</th>
                                        <th>{{ __('message.status') }}</th>
                                        <th>{{ __('message.created_at') }}</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($drivers))
                                        @foreach($drivers as $driver)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $driver['name'] ?? '-' }}</td>
                                                <td>{{ $driver['email'] ?? '-' }}</td>
                                                <td>{{ $driver['phone'] ?? '-' }}</td>
                                                <td>{{ $driver['cityId'] ?? ($driver['cityKey'] ?? '-') }}</td>
                                                <td>{{ $driver['verificationStatus'] ?? '-' }}</td>
                                                <td>{{ $driver['created_at'] ?? '-' }}</td>
                                                <td>
                                                    @if(!empty($driver['uid']) || !empty($driver['__id']))
                                                        @php
                                                            $driverId = $driver['uid'] ?? $driver['__id'];
                                                            $status = strtolower((string) ($driver['verificationStatus'] ?? $driver['status'] ?? ''));
                                                            $isPending = $status === 'pending';
                                                        @endphp
                                                        <a href="{{ route('driver.verify', ['uid' => $driverId]) }}" class="btn btn-sm btn-info">تحقق</a>
                                                        @if($isPending)
                                                            <form method="POST" action="{{ route('driver.approve', ['uid' => $driverId]) }}" class="d-inline-block" onsubmit="return confirm('تأكيد قبول السائق؟');">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-success">قبول</button>
                                                            </form>
                                                            <form method="POST" action="{{ route('driver.reject', ['uid' => $driverId]) }}" class="d-inline-block js-reject-form">
                                                                @csrf
                                                                <input type="hidden" name="reason" value="">
                                                                <button type="submit" class="btn btn-sm btn-danger">رفض</button>
                                                            </form>
                                                        @endif
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
    <script>
        document.querySelectorAll('.js-reject-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var reason = prompt('سبب الرفض (اختياري):');
                if (reason === null) {
                    e.preventDefault();
                    return;
                }
                var input = form.querySelector('input[name="reason"]');
                if (input) {
                    input.value = reason;
                }
                if (!confirm('تأكيد رفض السائق؟')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</x-master-layout>

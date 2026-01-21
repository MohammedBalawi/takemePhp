<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ route('driver.index') }}" class="btn border-radius-10 btn-sm btn-primary float-right" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @php
                                $driverName = $driver['name'] ?? ($driver['full_name'] ?? '');
                                $driverUid = $driver['uid'] ?? '';
                                $driverStatus = $driver['verificationStatus'] ?? '';
                            @endphp
                            <div><strong>{{ $driverName !== '' ? $driverName : '-' }}</strong></div>
                            <div class="text-muted">{{ $driverUid !== '' ? $driverUid : '-' }}</div>
                            <div class="text-muted">{{ $driverStatus !== '' ? $driverStatus : '-' }}</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>المستند</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $labels = [
                                            'profile' => 'صورة شخصية',
                                            'license' => 'رخصة',
                                            'insurance' => 'تأمين',
                                            'car_license' => 'رخصة سيارة',
                                            'car_image' => 'صورة سيارة',
                                        ];
                                    @endphp
                                    @forelse($links as $item)
                                        <tr>
                                            <td>{{ $labels[$item['label']] ?? $item['label'] }}</td>
                                            <td>
                                                @if(!empty($item['url']))
                                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">عرض</a>
                                                    <div class="mt-2">
                                                        <img src="{{ $item['url'] }}" alt="{{ $item['label'] }}" style="max-width:120px;max-height:60px;">
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير متوفر</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted">غير متوفر</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

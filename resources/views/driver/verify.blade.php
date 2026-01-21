<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ __('message.driver_document') }}</h4>
                        </div>
                        <a href="{{ route('driver.pending') }}" class="btn btn-sm btn-secondary">{{ __('message.back') }}</a>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5 class="mb-2">{{ __('message.driver') }}</h5>
                            <div>{{ __('message.name') }}: {{ $driver['name'] ?? '-' }}</div>
                            <div>{{ __('message.email') }}: {{ $driver['email'] ?? '-' }}</div>
                            <div>{{ __('message.contact_number') }}: {{ $driver['phone'] ?? '-' }}</div>
                            <div>{{ __('message.status') }}: {{ $driver['verificationStatus'] ?? ($driver['status'] ?? '-') }}</div>
                        </div>
                        <div>
                            <h5 class="mb-2">{{ __('message.document') }}</h5>
                            @if(!empty($links))
                                <ul class="list-unstyled">
                                    @foreach($links as $item)
                                        <li class="mb-2">
                                            <span class="font-weight-bold">{{ $item['label'] ?? '' }}:</span>
                                            @if(!empty($item['url']))
                                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener">عرض</a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-muted">لا يوجد بيانات</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

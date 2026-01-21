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
                                        <th>{{ __('message.status') }}</th>
                                        <th>Profile</th>
                                        <th>License</th>
                                        <th>Insurance</th>
                                        <th>Car License</th>
                                        <th>Car Image</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($driver_docs))
                                        @foreach($driver_docs as $driver)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $driver['name'] ?? '-' }}</td>
                                                <td>{{ $driver['email'] ?? '-' }}</td>
                                                <td>{{ $driver['phone'] ?? '-' }}</td>
                                                <td>{{ $driver['verificationStatus'] ?? '-' }}</td>
                                                <td>
                                                    @if(!empty($driver['docs']['profile']))
                                                        <a class="btn btn-sm btn-outline-primary" href="{{ $driver['docs']['profile'] }}" target="_blank" rel="noopener">View</a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($driver['docs']['license']))
                                                        <a class="btn btn-sm btn-outline-primary" href="{{ $driver['docs']['license'] }}" target="_blank" rel="noopener">View</a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($driver['docs']['insurance']))
                                                        <a class="btn btn-sm btn-outline-primary" href="{{ $driver['docs']['insurance'] }}" target="_blank" rel="noopener">View</a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($driver['docs']['car_license']))
                                                        <a class="btn btn-sm btn-outline-primary" href="{{ $driver['docs']['car_license'] }}" target="_blank" rel="noopener">View</a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($driver['docs']['car_image']))
                                                        <a class="btn btn-sm btn-outline-primary" href="{{ $driver['docs']['car_image'] }}" target="_blank" rel="noopener">View</a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="10">{{ __('message.no_record_found') }}</td>
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

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
                                        <th>{{ __('message.status') }}</th>
                                        <th>{{ __('message.created_at') }}</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($sub_admins))
                                        @foreach($sub_admins as $sub_admin)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $sub_admin['name'] ?? '-' }}</td>
                                                <td>{{ $sub_admin['email'] ?? '-' }}</td>
                                                <td>{{ !empty($sub_admin['is_active']) ? __('message.active') : __('message.inactive') }}</td>
                                                <td>{{ $sub_admin['created_at'] ?? '-' }}</td>
                                                <td><span class="text-muted">-</span></td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6">{{ __('message.no_record_found') }}</td>
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

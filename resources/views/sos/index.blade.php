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
                                        <th>العنوان</th>
                                        <th>الاسم</th>
                                        <th>رقم التواصل</th>
                                        <th>الموقع</th>
                                        <th>{{ __('message.created_at') }}</th>
                                        <th>{{ __('message.status') }}</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rows))
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['title_address'] ?? '-' }}</td>
                                                <td>{{ $row['actor_name'] ?? '-' }}</td>
                                                <td>{{ $row['actor_phone'] ?? '-' }}</td>
                                                <td>
                                                    <div>{{ $row['location_text'] ?? '-' }}</div>
                                                    @if(!empty($row['map_url']))
                                                        <a href="{{ $row['map_url'] }}" target="_blank" rel="noopener">Map</a>
                                                    @endif
                                                </td>
                                                <td>{{ $row['created_at'] ?? '-' }}</td>
                                                <td>{{ $row['status'] ?? '-' }}</td>
                                                <td><span class="text-muted">View</span></td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="8">لا توجد بيانات</td>
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

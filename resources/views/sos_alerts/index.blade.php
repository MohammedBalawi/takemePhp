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
                                        <th>created_at</th>
                                        <th>ride_request_id</th>
                                        <th>latitude</th>
                                        <th>longitude</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rows))
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['created_at'] ?? '-' }}</td>
                                                <td>{{ $row['ride_request_id'] ?? '-' }}</td>
                                                <td>{{ $row['latitude'] ?? '-' }}</td>
                                                <td>{{ $row['longitude'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5">لا يوجد بيانات</td>
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

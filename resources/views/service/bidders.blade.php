<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{ route('service.index') }}" class="float-right btn btn-sm border-radius-10 btn-primary me-2" role="button">
                            <i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>السائق</th>
                                        <th>الهاتف</th>
                                        <th>Online</th>
                                        <th>Available</th>
                                        <th>{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($rows))
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['name'] ?? '-' }}</td>
                                                <td>{{ $row['phone'] ?? '-' }}</td>
                                                <td>{{ !empty($row['is_online']) ? 'نعم' : 'لا' }}</td>
                                                <td>{{ !empty($row['is_available']) ? 'نعم' : 'لا' }}</td>
                                                <td>
                                                    <form method="POST" action="{{ route('service.approve', $offerId) }}">
                                                        @csrf
                                                        <input type="hidden" name="driverUid" value="{{ $row['uid'] ?? '' }}">
                                                        <button class="btn btn-sm btn-primary" type="submit">اعتماد</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6">لا يوجد بيانات</td>
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

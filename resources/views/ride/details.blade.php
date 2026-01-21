<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">{{ $pageTitle ?? __('message.riderequest') }}</h4>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card card-block border-radius-20">
                    <div class="card-body">
                        @if(!empty($data))
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                    @foreach($data as $key => $value)
                                        <tr>
                                            <th>{{ $key }}</th>
                                            <td>{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mb-0">{{ __('message.no_record_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

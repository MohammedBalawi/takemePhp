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
                            <a href="{{ url()->previous() }}" class="btn border-radius-10 btn-sm btn-primary float-right" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(!empty($data))
                            <div class="table-responsive">
                                <table class="table table-striped text-center">
                                    <tbody>
                                        <tr>
                                            <th>Source</th>
                                            <td>{{ $source }}</td>
                                        </tr>
                                        @foreach($data as $key => $value)
                                            @if(is_array($value))
                                                <tr>
                                                    <th>{{ $key }}</th>
                                                    <td><pre class="mb-0 text-left">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre></td>
                                                </tr>
                                            @else
                                                <tr>
                                                    <th>{{ $key }}</th>
                                                    <td>{{ $value }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-center text-muted mb-0">{{ __('message.no_record_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

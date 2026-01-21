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
                            <p><strong>Source:</strong> {{ $source }}</p>
                            <p><strong>ID:</strong> {{ $id }}</p>
                            <pre class="text-left mb-0">{{ json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            <p class="text-center text-muted mb-0">{{ __('message.no_record_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{ route('monthly.airports.index') }}" class="float-right btn btn-sm border-radius-10 btn-primary me-2">
                            <i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('monthly.airports.store') }}">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">وقت السائق</label>
                                    <input type="text" name="driver_time" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">الحالة</label>
                                    <input type="text" name="status" class="form-control" value="pending">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-control-label">من (العنوان)</label>
                                    <input type="text" name="from_address" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">lat</label>
                                    <input type="text" name="from_lat" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">lng</label>
                                    <input type="text" name="from_lng" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-control-label">إلى (العنوان)</label>
                                    <input type="text" name="to_address" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">lat</label>
                                    <input type="text" name="to_lat" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">lng</label>
                                    <input type="text" name="to_lng" class="form-control">
                                </div>
                            </div>
                            <hr>
                            <button type="submit" class="btn border-radius-10 btn-primary float-right">حفظ</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

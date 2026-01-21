<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{ route('monthly.employee.index') }}" class="float-right btn btn-sm border-radius-10 btn-primary me-2">
                            <i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('monthly.employee.store') }}">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">الهاتف *</label>
                                    <input type="text" name="phone" class="form-control" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">عدد الأشخاص</label>
                                    <input type="text" name="persons" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">أيام العمل</label>
                                    <input type="text" name="days_count" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-control-label">من (العنوان)</label>
                                    <input type="text" name="home_address" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">lat</label>
                                    <input type="text" name="home_lat" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">lng</label>
                                    <input type="text" name="home_lng" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-control-label">إلى (العنوان)</label>
                                    <input type="text" name="dest_address" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">lat</label>
                                    <input type="text" name="dest_lat" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">lng</label>
                                    <input type="text" name="dest_lng" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">وقت وصول السائق</label>
                                    <input type="text" name="driver_arrival_time" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">وقت البداية</label>
                                    <input type="text" name="start_time" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">وقت النهاية</label>
                                    <input type="text" name="end_time" class="form-control">
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="form-control-label">الشفتات (JSON)</label>
                                    <textarea name="shifts" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="form-control-label">ملاحظات</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
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

<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{ route('pricing.index') }}" class="float-right btn btn-sm border-radius-10 btn-primary me-2">
                            <i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('pricing.store') }}">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">City ID (اختياري)</label>
                                    <input type="text" name="city_id" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">City Name</label>
                                    <input type="text" name="city_name" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Service ID *</label>
                                    <input type="text" name="service_id" class="form-control" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Currency</label>
                                    <input type="text" name="currency" class="form-control" value="SAR">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Base Fare *</label>
                                    <input type="number" name="base_fare" class="form-control" step="any" min="0" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Per KM *</label>
                                    <input type="number" name="per_km" class="form-control" step="any" min="0" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Per Min *</label>
                                    <input type="number" name="per_min" class="form-control" step="any" min="0" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Min Fare *</label>
                                    <input type="number" name="min_fare" class="form-control" step="any" min="0" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Active</label>
                                    <select name="is_active" class="form-control select2js">
                                        <option value="1">نعم</option>
                                        <option value="0">لا</option>
                                    </select>
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

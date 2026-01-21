<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{ route('pricing_modifiers.index') }}" class="float-right btn btn-sm border-radius-10 btn-primary me-2">
                            <i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('pricing_modifiers.store') }}">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">نوع القاعدة *</label>
                                    <select name="type" class="form-control select2js" required>
                                        <option value="time">time</option>
                                        <option value="weather">weather</option>
                                        <option value="surge">surge</option>
                                        <option value="place">place</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">City ID (اختياري)</label>
                                    <input type="text" name="city_id" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">City Name (اختياري)</label>
                                    <input type="text" name="city_name" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Service ID *</label>
                                    <input type="text" name="service_id" class="form-control" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Currency</label>
                                    <input type="text" name="currency" class="form-control" value="SAR">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Modifier Mode *</label>
                                    <select name="modifier_mode" class="form-control select2js" required>
                                        <option value="percent">percent</option>
                                        <option value="fixed">fixed</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Modifier Value *</label>
                                    <input type="number" name="modifier_value" class="form-control" step="any" min="0" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Override Base Fare</label>
                                    <input type="number" name="override_base_fare" class="form-control" step="any" min="0">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Override Per KM</label>
                                    <input type="number" name="override_per_km" class="form-control" step="any" min="0">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Override Per Min</label>
                                    <input type="number" name="override_per_min" class="form-control" step="any" min="0">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">Override Min Fare</label>
                                    <input type="number" name="override_min_fare" class="form-control" step="any" min="0">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Day</label>
                                    <select name="day" class="form-control select2js">
                                        <option value="all">all</option>
                                        <option value="sun">sun</option>
                                        <option value="mon">mon</option>
                                        <option value="tue">tue</option>
                                        <option value="wed">wed</option>
                                        <option value="thu">thu</option>
                                        <option value="fri">fri</option>
                                        <option value="sat">sat</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-control-label">Start</label>
                                    <input type="time" name="start_time" class="form-control">
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-control-label">End</label>
                                    <input type="time" name="end_time" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Weather</label>
                                    <input type="text" name="weather" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Place Key</label>
                                    <input type="text" name="place_key" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Place Name</label>
                                    <input type="text" name="place_name" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Zone ID</label>
                                    <input type="text" name="zone_id" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Surge Tag</label>
                                    <input type="text" name="surge_tag" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Priority</label>
                                    <input type="number" name="priority" class="form-control" min="0" value="0">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">Active</label>
                                    <select name="is_active" class="form-control select2js">
                                        <option value="1">نعم</option>
                                        <option value="0">لا</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="form-control-label">Description</label>
                                    <input type="text" name="description" class="form-control">
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

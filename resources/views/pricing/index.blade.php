<x-master-layout :assets="$assets ?? []">
    @php
        $isActiveValue = old('isActive', $fields['isActive'] ?? false);
        $isActive = filter_var($isActiveValue, FILTER_VALIDATE_BOOLEAN);
        $disableFields = !$isActive;
    @endphp
    <div class="container-fluid pricing-page">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('pricing.update') }}">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="form-control-label">آخر تحديث</label>
                                    <input type="text" class="form-control" value="{{ $updatedAt ?: '-' }}" readonly>
                                </div>
                                @if(isset($fields['currency']))
                                    <div class="form-group col-md-3">
                                        <label class="form-control-label">العملة</label>
                                        <input type="text" class="form-control" value="{{ $fields['currency'] }}" readonly>
                                    </div>
                                @endif
                                @if(isset($fields['name']))
                                    <div class="form-group col-md-3">
                                        <label class="form-control-label">الاسم</label>
                                        <input type="text" class="form-control" value="{{ $fields['name'] }}" readonly>
                                    </div>
                                @endif
                                <div class="form-group col-md-12">
                                    <label class="form-control-label">الحالة / مُفعل</label>
                                    <input type="hidden" name="isActive" value="0">
                                    <div class="custom-control custom-switch pricing-status">
                                        <input type="checkbox" class="custom-control-input" id="pricing-is-active" name="isActive" value="1" {{ $isActive ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="pricing-is-active">مُفعل</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">أجرة البداية</label>
                                    <input type="number" name="baseFare" class="form-control pricing-field" step="any" value="{{ old('baseFare', $fields['baseFare'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">رسوم الحجز</label>
                                    <input type="number" name="bookingFee" class="form-control pricing-field" step="any" value="{{ old('bookingFee', $fields['bookingFee'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">رسوم الإلغاء</label>
                                    <input type="number" name="cancelFee" class="form-control pricing-field" step="any" value="{{ old('cancelFee', $fields['cancelFee'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">الحد الأدنى للأجرة</label>
                                    <input type="number" name="minimumFare" class="form-control pricing-field" step="any" value="{{ old('minimumFare', $fields['minimumFare'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">سعر الكيلومتر</label>
                                    <input type="number" name="perKm" class="form-control pricing-field" step="any" value="{{ old('perKm', $fields['perKm'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">سعر الدقيقة</label>
                                    <input type="number" name="perMin" class="form-control pricing-field" step="any" value="{{ old('perMin', $fields['perMin'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">بداية وقت الليل</label>
                                    <input type="number" name="nightStartHour" class="form-control pricing-field" step="any" value="{{ old('nightStartHour', $fields['nightStartHour'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">نهاية وقت الليل</label>
                                    <input type="number" name="nightEndHour" class="form-control pricing-field" step="any" value="{{ old('nightEndHour', $fields['nightEndHour'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">معامل الليل</label>
                                    <input type="number" name="nightMultiplier" class="form-control pricing-field" step="any" value="{{ old('nightMultiplier', $fields['nightMultiplier'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">معامل الزيادة الافتراضي</label>
                                    <input type="number" name="surgeMultiplierDefault" class="form-control pricing-field" step="any" value="{{ old('surgeMultiplierDefault', $fields['surgeMultiplierDefault'] ?? '') }}" {{ $disableFields ? 'disabled readonly' : '' }}>
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
    <script>
        (function () {
            var toggle = document.getElementById('pricing-is-active');
            if (!toggle) {
                return;
            }
            var fields = document.querySelectorAll('.pricing-field');
            var setDisabled = function (disabled) {
                fields.forEach(function (field) {
                    field.disabled = disabled;
                    if (disabled) {
                        field.setAttribute('readonly', 'readonly');
                    } else {
                        field.removeAttribute('readonly');
                    }
                });
            };
            setDisabled(!toggle.checked);
            toggle.addEventListener('change', function () {
                setDisabled(!toggle.checked);
            });
        })();
    </script>
    <style>
        .pricing-page {
            direction: rtl;
        }
        .pricing-page .form-control-label {
            display: block;
            text-align: right;
        }
        .pricing-page .pricing-status {
            display: flex;
            flex-direction: row-reverse;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
        }
        .pricing-page .pricing-status .custom-control-label {
            text-align: right;
        }
    </style>
</x-master-layout>

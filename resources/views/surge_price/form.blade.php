<x-master-layout :assets="$assets ?? []">
    <div>
        {!! Form::open(['route' => ['surge-prices.store'], 'method' => 'post']) !!}
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{route('surge-prices.index')}}" class="float-right btn btn-sm border-radius-10 btn-primary me-2" role="button">
                            <i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    {{ Form::label('city_name','المدينة',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('city_name', old('city_name'),['placeholder' => 'المدينة','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('city_id','City ID (اختياري)',['class'=>'form-control-label']) }}
                                    {{ Form::text('city_id', old('city_id'),['placeholder' => 'cityId','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('service_id','الخدمة',['class'=>'form-control-label']) }}
                                    {{ Form::text('service_id', old('service_id'),['placeholder' => 'serviceId','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('rule_type','نوع القاعدة <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::select('rule_type',[ 'base_price' => 'تسعيرة ثابتة', 'time_price' => 'تسعيرة وقت', 'weather_price' => 'تسعيرة جو', 'traffic_surge' => 'زحام', 'place_modifier' => 'مكان/منطقة' ], old('rule_type'), [ 'class' =>'form-control select2js','required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('increase_type','نوع الزيادة <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::select('increase_type',[ 'percent' => 'نسبة', 'fixed' => 'ثابت' ], old('increase_type'), [ 'class' =>'form-control select2js','required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('increase_value','قيمة الزيادة <span class="text-danger">*</span>',['class' => 'form-control-label'], false ) }}
                                    {{ Form::number('increase_value', old('increase_value'),[ 'step' =>'any', 'min' =>'0', 'placeholder' => 'القيمة', 'class' => 'form-control','required']) }}
                                </div>

                                <div class="form-group col-md-3">
                                    {{ Form::label('base_fare','Base Fare',['class'=>'form-control-label']) }}
                                    {{ Form::number('base_fare', old('base_fare'),[ 'step' =>'any', 'min' =>'0', 'placeholder' => '0', 'class' => 'form-control']) }}
                                </div>

                                <div class="form-group col-md-3">
                                    {{ Form::label('per_km','Per KM',['class'=>'form-control-label']) }}
                                    {{ Form::number('per_km', old('per_km'),[ 'step' =>'any', 'min' =>'0', 'placeholder' => '0', 'class' => 'form-control']) }}
                                </div>

                                <div class="form-group col-md-3">
                                    {{ Form::label('per_min','Per Min',['class'=>'form-control-label']) }}
                                    {{ Form::number('per_min', old('per_min'),[ 'step' =>'any', 'min' =>'0', 'placeholder' => '0', 'class' => 'form-control']) }}
                                </div>

                                <div class="form-group col-md-3">
                                    {{ Form::label('min_fare','Min Fare',['class'=>'form-control-label']) }}
                                    {{ Form::number('min_fare', old('min_fare'),[ 'step' =>'any', 'min' =>'0', 'placeholder' => '0', 'class' => 'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('weather','الحالات الجوية',['class'=>'form-control-label']) }}
                                    {{ Form::select('weather',[ '' => 'بدون', 'clear' => 'Clear', 'rain' => 'Rain', 'thunderstorm' => 'Thunderstorm', 'fog' => 'Fog', 'wind' => 'Wind', 'dust' => 'Dust' ], old('weather'), [ 'class' =>'form-control select2js']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('day','اليوم',['class'=>'form-control-label']) }}
                                    {{ Form::select('day',[ 'all' => 'كل الأيام', 'sun' => 'الأحد', 'mon' => 'الاثنين', 'tue' => 'الثلاثاء', 'wed' => 'الأربعاء', 'thu' => 'الخميس', 'fri' => 'الجمعة', 'sat' => 'السبت' ], old('day','all'), [ 'class' =>'form-control select2js']) }}
                                </div>

                                <div class="form-group col-md-2">
                                    {{ Form::label('time_from','من الوقت',['class' => 'form-control-label']) }}
                                    {{ Form::time('time_from', old('time_from'), ['class' => 'form-control']) }}
                                </div>

                                <div class="form-group col-md-2">
                                    {{ Form::label('time_to','إلى الوقت',['class' => 'form-control-label']) }}
                                    {{ Form::time('time_to', old('time_to'), ['class' => 'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('place_key','المكان/المنطقة',['class'=>'form-control-label']) }}
                                    {{ Form::text('place_key', old('place_key'),['placeholder' => 'نص حر','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('description','الوصف',['class'=>'form-control-label']) }}
                                    {{ Form::text('description', old('description'),['placeholder' => 'الوصف','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-3">
                                    {{ Form::label('status','الحالة',['class'=>'form-control-label']) }}
                                    {{ Form::select('status',[ 'active' => 'نشط', 'inactive' => 'غير نشط' ], old('status','active'), [ 'class' =>'form-control select2js','required']) }}
                                </div>
                            </div>
                            <hr>
                            {{ Form::submit(__('message.save'), ['class'=>'btn border-radius-10 btn-primary float-right']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
</x-master-layout>

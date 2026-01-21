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
                                    {{ Form::select('rule_type',[ 'weather' => 'جو', 'surge' => 'زحام', 'fixed' => 'ثابت', 'city' => 'مدينة', 'time' => 'وقت', 'place' => 'مكان' ], old('rule_type', $type ?? ''), [ 'class' =>'form-control select2js','required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('modifier_mode','نوع الزيادة <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::select('modifier_mode',[ 'percent' => 'نسبة', 'fixed' => 'ثابت' ], old('modifier_mode'), [ 'class' =>'form-control select2js','required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('modifier_value','قيمة الزيادة <span class="text-danger">*</span>',['class' => 'form-control-label'], false ) }}
                                    {{ Form::number('modifier_value', old('modifier_value'),[ 'step' =>'any', 'min' =>'0', 'placeholder' => 'القيمة', 'class' => 'form-control','required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('scope','النطاق',['class'=>'form-control-label']) }}
                                    {{ Form::select('scope',[ 'global' => 'global', 'city' => 'city', 'service' => 'service', 'city_service' => 'city_service' ], old('scope','global'), [ 'class' =>'form-control select2js']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('weather_condition','الحالات الجوية',['class'=>'form-control-label']) }}
                                    {{ Form::select('weather_condition',[ '' => 'بدون', 'any' => 'any', 'rain' => 'rain', 'storm' => 'storm', 'fog' => 'fog' ], old('weather_condition'), [ 'class' =>'form-control select2js']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('day','اليوم',['class'=>'form-control-label']) }}
                                    {{ Form::select('day',[ 'all' => 'كل الأيام', 'sun' => 'الأحد', 'mon' => 'الاثنين', 'tue' => 'الثلاثاء', 'wed' => 'الأربعاء', 'thu' => 'الخميس', 'fri' => 'الجمعة', 'sat' => 'السبت' ], old('day','all'), [ 'class' =>'form-control select2js']) }}
                                </div>

                                <div class="form-group col-md-2">
                                    {{ Form::label('start_time','من الوقت',['class' => 'form-control-label']) }}
                                    {{ Form::time('start_time', old('start_time'), ['class' => 'form-control']) }}
                                </div>

                                <div class="form-group col-md-2">
                                    {{ Form::label('end_time','إلى الوقت',['class' => 'form-control-label']) }}
                                    {{ Form::time('end_time', old('end_time'), ['class' => 'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('place_key','المكان/المنطقة',['class'=>'form-control-label']) }}
                                    {{ Form::text('place_key', old('place_key'),['placeholder' => 'نص حر','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('place_name','اسم المكان',['class'=>'form-control-label']) }}
                                    {{ Form::text('place_name', old('place_name'),['placeholder' => 'نص حر','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('zone_id','Zone ID',['class'=>'form-control-label']) }}
                                    {{ Form::text('zone_id', old('zone_id'),['placeholder' => 'zoneId','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('surge_tag','Surge Tag',['class'=>'form-control-label']) }}
                                    {{ Form::text('surge_tag', old('surge_tag'),['placeholder' => 'traffic/crowd/any','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('description','الوصف',['class'=>'form-control-label']) }}
                                    {{ Form::text('description', old('description'),['placeholder' => 'الوصف','class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-3">
                                    {{ Form::label('status','الحالة',['class'=>'form-control-label']) }}
                                    {{ Form::select('status',[ 'active' => 'نشط', 'inactive' => 'غير نشط' ], old('status','active'), [ 'class' =>'form-control select2js','required']) }}
                                </div>

                                <div class="form-group col-md-3">
                                    {{ Form::label('priority','الأولوية',['class'=>'form-control-label']) }}
                                    {{ Form::number('priority', old('priority',0),[ 'min' =>'0', 'class' => 'form-control']) }}
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

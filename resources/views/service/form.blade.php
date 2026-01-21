<x-master-layout :assets="$assets ?? []">
    <div>
        {!! Form::open(['route' => ['service.store'], 'method' => 'post']) !!}
        <div class="row">
            <div class="col-lg-12 mt-3">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between"  style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{route('service.index')}}" class="float-right btn btn-sm border-radius-10 btn-primary me-2" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
                    </div>

                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    {{ Form::label('title','العنوان <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('title',old('title'),['placeholder' => 'عنوان الخدمة','class' =>'form-control','required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('base_price','السعر الأساسي <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::number('base_price', old('base_price'), ['class' => 'form-control', 'min' => 0, 'step' => 'any', 'required', 'placeholder' => 'السعر الأساسي' ]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('currency','العملة',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('currency', old('currency','SAR'), ['class' => 'form-control', 'placeholder' => 'SAR' ]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('discount_percent','نسبة الخصم',['class'=>'form-control-label'], false ) }}
                                    {{ Form::number('discount_percent', old('discount_percent'), ['class' => 'form-control', 'min' => 0, 'step' => 'any', 'placeholder' => '0' ]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('pickup_address','موقع الانطلاق',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('pickup_address', old('pickup_address'), ['class' => 'form-control', 'placeholder' => 'عنوان الانطلاق' ]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('dropoff_address','موقع الوجهة',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('dropoff_address', old('dropoff_address'), ['class' => 'form-control', 'placeholder' => 'عنوان الوجهة' ]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('rider_name','اسم الراكب',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('rider_name', old('rider_name'), ['class' => 'form-control', 'placeholder' => 'اسم الراكب' ]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('rider_phone','رقم الراكب',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('rider_phone', old('rider_phone'), ['class' => 'form-control', 'placeholder' => 'رقم التواصل' ]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('ride_id','Ride ID',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('ride_id', old('ride_id'), ['class' => 'form-control', 'placeholder' => 'Ride ID' ]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('status','الحالة',['class'=>'form-control-label'],false) }}
                                    {{ Form::select('status',[ 'new' => 'جديد', 'approved' => 'موافق عليه', 'assigned' => 'تم الإسناد' ], old('status','new'), [ 'class' =>'form-control select2js']) }}
                                </div>
                            </div>
                            <hr>
                            {{ Form::submit( __('message.save'), ['class'=>'btn border-radius-10 btn-primary float-right']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
</x-master-layout>

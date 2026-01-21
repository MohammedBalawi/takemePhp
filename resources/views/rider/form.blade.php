<x-master-layout :assets="$assets ?? []">
    <div>
        {!! Form::open(['route' => ['rider.store'], 'method' => 'post', 'id' => 'rider_form']) !!}
        <div class="row">
            <div class="col-xl-3 col-lg-4 mt-3">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between"  style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">{{ __('message.status') }}</label>
                            <div class="row" style="--bs-gap: 1rem;">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        {{ Form::radio('status', 'active' , old('status') || true, ['class' => 'form-check-input', 'id' => 'status-active' ]) }}
                                        {{ Form::label('status-active', __('message.active'), ['class' => 'form-check-label']) }}
                                    </div>
                                    <div class="form-check">
                                        {{ Form::radio('status', 'inactive', old('status') === 'inactive', ['class' => 'form-check-input', 'id' => 'status-inactive']) }}
                                        {{ Form::label('status-inactive', __('message.inactive'), ['class' => 'form-check-label']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        {{ Form::radio('status', 'pending', old('status') === 'pending', ['class' => 'form-check-input', 'id' => 'status-pending']) }}
                                        {{ Form::label('status-pending', __('message.pending'), ['class' => 'form-check-label']) }}
                                    </div>
                                    <div class="form-check">
                                        {{ Form::radio('status', 'banned', old('status') === 'banned', ['class' => 'form-check-input', 'id' => 'status-banned']) }}
                                        {{ Form::label('status-banned', __('message.banned'), ['class' => 'form-check-label']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-lg-8 mt-3">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }} {{ __('message.information') }}</h4>
                        </div>
                        <a href="{{route('rider.index')}}" class="btn border-radius-10 btn-sm btn-primary float-right" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
                    </div>
                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    {{ Form::label('first_name',__('message.first_name').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('first_name',old('first_name'),['placeholder' => __('message.first_name'),'class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('last_name',__('message.last_name').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('last_name',old('last_name'),['placeholder' => __('message.last_name'),'class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('email',__('message.email').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::email('email', old('email'), [ 'placeholder' => __('message.email'), 'class' => 'form-control']) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('username',__('message.username').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('username', old('username'), ['class' => 'form-control', 'placeholder' => __('message.username') ]) }}
                                </div>
                                <div class="form-group col-md-6">
                                    {{ Form::label('password',__('message.password').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::password('password', ['class' => 'form-control', 'placeholder' =>  __('message.password') ]) }}
                                </div>
                                <div class="form-group col-md-6">
                                    {{ Form::label('phone',__('message.contact_number').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('phone', '+966',[ 'placeholder' => __('message.contact_number'), 'class' => 'form-control', 'id' => 'phone' ]) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('address',__('message.address'), ['class' => 'form-control-label']) }}
                                    {{ Form::textarea('address', null, ['class'=>"form-control textarea" , 'rows'=>3  , 'placeholder'=> __('message.address') ]) }}
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

@section('bottom_script')
    <script>
        $(document).ready(function(){
            formValidation("#rider_form", {
                first_name: { required: true },
                last_name: { required: true },
                email: { required: true, email: true },
                username: { required: true },
                password: { required: true, minlength: 6 },
                phone: { required: true },
            }, {
                first_name: { required: "{{__('message.please_enter_first_name')}}" },
                last_name: { required: "{{__('message.please_enter_last_name')}}" },
                email: { required: "{{__('message.please_enter_email')}}", email: "{{__('message.please_enter_valid_email')}}" },
                username: { required: "{{__('message.please_enter_username')}}" },
                password: { required: "{{__('message.please_enter_password')}}" },
                phone: { required: "{{__('message.please_enter_contact_number')}}" },
            });
        });
    </script>
@endsection

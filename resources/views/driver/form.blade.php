<x-master-layout :assets="$assets ?? []">
    <div>
        {!! Form::open(['route' => ['driver.store'], 'method' => 'post', 'id' => 'driver_form']) !!}
        <div class="row">
            <div class="col-xl-3 col-lg-4 mt-3">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">{{ __('message.status') }}</label>
                            {{ Form::select('verificationStatus', [
                                'pending' => __('message.pending'),
                                'approved' => __('message.approved'),
                                'rejected' => __('message.reject')
                            ], old('verificationStatus','pending'), ['class' => 'form-control select2js']) }}
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
                        <div class="card-action">
                            <a href="{{ route('driver.index') }}" class="float-right btn btn-sm border-radius-10 btn-primary me-2" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    {{ Form::label('name', __('message.name').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    {{ Form::text('name', old('name'), ['placeholder' => __('message.name'), 'class' => 'form-control', 'required']) }}
                                </div>
                                <div class="form-group col-md-6">
                                    {{ Form::label('email', __('message.email').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    {{ Form::email('email', old('email'), ['placeholder' => __('message.email'), 'class' => 'form-control', 'required']) }}
                                </div>
                                <div class="form-group col-md-6">
                                    {{ Form::label('phone', __('message.contact_number').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    {{ Form::text('phone', old('phone', '+966'), ['placeholder' => __('message.contact_number'), 'class' => 'form-control', 'required']) }}
                                </div>
                                <div class="form-group col-md-6">
                                    {{ Form::label('username', __('message.username').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    {{ Form::text('username', old('username'), ['placeholder' => __('message.username'), 'class' => 'form-control', 'required']) }}
                                </div>
                                <div class="form-group col-md-6">
                                    {{ Form::label('cityId', __('message.city').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    {{ Form::text('cityId', old('cityId'), ['placeholder' => __('message.city'), 'class' => 'form-control', 'required']) }}
                                </div>
                                <div class="form-group col-md-6">
                                    {{ Form::label('password', __('message.password').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    <div class="input-group">
                                        {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('message.password'), 'id' => 'driver-password', 'required']) }}
                                        <div class="input-group-append">
                                            <span class="input-group-text hide-show-password" style="cursor: pointer;">
                                                <i class="fas fa-eye-slash"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            {{ Form::submit(__('message.save'), ['class' => 'btn border-radius-10 btn-primary float-right']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
    @section('bottom_script')
        <script>
            $(document).ready(function() {
                $('.hide-show-password').on('click', function() {
                    var passwordInput = $('#driver-password');
                    var eyeIcon = $('.hide-show-password i');

                    var passwordFieldType = passwordInput.attr('type');
                    if (passwordFieldType === 'password') {
                        passwordInput.attr('type', 'text');
                        eyeIcon.removeClass('fa-eye-slash').addClass('fa-eye');
                    } else {
                        passwordInput.attr('type', 'password');
                        eyeIcon.removeClass('fa-eye').addClass('fa-eye-slash');
                    }
                });
            });
        </script>
    @endsection
</x-master-layout>

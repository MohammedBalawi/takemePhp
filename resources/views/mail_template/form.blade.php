<x-master-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        {!! Form::open(['route' => ['mail-template.store'], 'method' => 'post']) !!}
        {!! Form::hidden('type',$type) !!}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-radius-20">
                        <div class="card-header d-flex justify-content-between border-bottom-0"  style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                            <div class="header-title">
                                <h4 class="card-title">{{ $pageTitle ?? __('message.list') }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card border-radius-20">
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    {{ Form::label('template_type', __('message.type'), ['class' => 'form-control-label'], false) }}
                                    <div class="form-control bg-light">{{ $type }}</div>
                                </div>
                                <div class="form-group col-md-6">
                                    {{ Form::label('subject', __('message.subject').' <span class="text-danger">*</span>', ['class' => 'form-control-label'],false) }}
                                    {{ Form::text('subject', $data['subject'] ?? old('subject'), ['placeholder' => __('message.subject'), 'class' => 'form-control', 'required']) }}
                                </div>
                                <div class="form-group col-md-12">
                                    {{ Form::label('body_html', __('message.mail_description'), ['class' => 'form-control-label'], false) }}
                                    {{ Form::textarea('description', $data['body_html'] ?? old('description'), ['rows' => 5,'class'=> 'form-control tinymce-mail_description', 'placeholder'=> __('message.mail_description')]) }}
                                </div>
                                <div class="form-group col-md-12">
                                    {{ Form::label('body_text', __('message.description'), ['class' => 'form-control-label'], false) }}
                                    {{ Form::textarea('body_text', $data['body_text'] ?? old('body_text'), ['rows' => 4,'class'=> 'form-control', 'placeholder'=> __('message.description')]) }}
                                </div>
                            </div>
                            {{ Form::submit( __('message.save'), ['class'=>'btn border-radius-10 btn-primary float-right']) }}
                        </div>
                    </div>
                </div>
            </div>
            @if(!empty($data['body_html']))
                <div class="row">
                    <div class="col-lg-12 mt-3">
                        <div class="card border-radius-20">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Preview</h5>
                            </div>
                            <div class="card-body">
                                {!! $data['body_html'] !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        {{ Form::close() }}
    </div>
    @section('bottom_script')
        <script>
            (function($) {
                $(document).ready(function(){
                    tinymceEditor('.tinymce-mail_description',' ',function (ed) {
                    }, 450)
                });
            })(jQuery);
        </script>
    @endsection
</x-master-layout>

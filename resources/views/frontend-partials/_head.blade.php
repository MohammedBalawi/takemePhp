@php
    $meta_description = AppSetting('meta_description', '');
    $favicon_url = asset('favicon.ico');
@endphp
<title>Take Me</title>
<meta name="description" content="{{ $meta_description }}">
<link rel="icon" type="image/x-icon" href="{{ $favicon_url }}">
<link rel="stylesheet" href="{{ asset('frontend-website/assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-website/assets/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css') }}"/>

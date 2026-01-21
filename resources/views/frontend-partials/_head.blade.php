@php
    $site_title = AppSetting('site_title', 'Take Me');
    $meta_description = AppSetting('meta_description', '');
    $site_favicon = AppSetting('site_favicon', '');
    $favicon_url = $site_favicon !== '' ? $site_favicon : asset('favicon.ico');
@endphp
<title>{{ $site_title }}</title>
<meta name="description" content="{{ $meta_description }}">
<link rel="icon" type="image/x-icon" href="{{ $favicon_url }}">
<link rel="stylesheet" href="{{ asset('frontend-website/assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-website/assets/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css') }}"/>

@props([
    'title' => null,
    'description' => null,
    'robots' => null,
    'canonical' => null,
    'image' => null,
    'type' => null,
    'area' => 'public',
    'locale' => null,
    'renderTitle' => true,
    'renderDescription' => true,
])

@php
    $meta = \App\Support\Seo\SeoManager::make([
        'title' => $title,
        'description' => $description,
        'robots' => $robots,
        'canonical' => $canonical,
        'image' => $image,
        'type' => $type,
        'area' => $area,
        'locale' => $locale,
    ])->toArray();
@endphp

@if($renderTitle)
<title>{{ $meta['title'] }}</title>
@endif
@if($renderDescription && !empty($meta['description']))
<meta name="description" content="{{ $meta['description'] }}">
@endif
<meta name="robots" content="{{ $meta['robots'] }}">
<meta name="googlebot" content="{{ $meta['robots'] }}">
@if(!empty($meta['canonical']))
<link rel="canonical" href="{{ $meta['canonical'] }}">
@endif
<meta property="og:site_name" content="{{ $meta['site_name'] }}">
<meta property="og:title" content="{{ $meta['title'] }}">
@if(!empty($meta['description']))
<meta property="og:description" content="{{ $meta['description'] }}">
@endif
<meta property="og:type" content="{{ $meta['type'] }}">
<meta property="og:locale" content="{{ $meta['locale'] }}">
@if(!empty($meta['canonical']))
<meta property="og:url" content="{{ $meta['canonical'] }}">
@endif
@if(!empty($meta['image']))
<meta property="og:image" content="{{ $meta['image'] }}">
@endif
<meta name="twitter:card" content="{{ !empty($meta['image']) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $meta['title'] }}">
@if(!empty($meta['description']))
<meta name="twitter:description" content="{{ $meta['description'] }}">
@endif
@if(!empty($meta['image']))
<meta name="twitter:image" content="{{ $meta['image'] }}">
@endif

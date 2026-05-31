@props([
    'title' => null,
    'description' => null,
    'robots' => null,
    'canonical' => null,
    'image' => null,
    'imageWidth' => null,
    'imageHeight' => null,
    'imageAlt' => null,
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
        'image_width' => $imageWidth,
        'image_height' => $imageHeight,
        'image_alt' => $imageAlt,
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
@if(!empty($meta['image_width']))
<meta property="og:image:width" content="{{ $meta['image_width'] }}">
@endif
@if(!empty($meta['image_height']))
<meta property="og:image:height" content="{{ $meta['image_height'] }}">
@endif
@if(!empty($meta['image_alt']))
<meta property="og:image:alt" content="{{ $meta['image_alt'] }}">
@endif
@endif
<meta name="twitter:card" content="{{ !empty($meta['image']) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $meta['title'] }}">
@if(!empty($meta['description']))
<meta name="twitter:description" content="{{ $meta['description'] }}">
@endif
@if(!empty($meta['image']))
<meta name="twitter:image" content="{{ $meta['image'] }}">
@if(!empty($meta['image_alt']))
<meta name="twitter:image:alt" content="{{ $meta['image_alt'] }}">
@endif
@endif

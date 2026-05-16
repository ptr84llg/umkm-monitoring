@extends('layouts.dashboard')
@section('title','Survei Saya')
@section('content')<x-umkm.card>@if($targeted)<p>Anda ditargetkan untuk survei. Silakan ikuti instrumen yang tersedia.</p>@else<p>Belum ada survei yang ditugaskan.</p>@endif</x-umkm.card>@endsection

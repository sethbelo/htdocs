{{-- Papier à en-tête SOGEREF pour courriers --}}
@extends('pdf.layout')

@section('content')
    <table><tr><td></td><td class="right" style="width: 45%;">Kinshasa, le {{ now()->translatedFormat('j F Y') }}</td></tr></table>
    <div style="margin-top: 40px; min-height: 500px; font-size: 11.5px; line-height: 1.7;">
        {!! $body ? nl2br(e($body)) : '<span class="muted">Objet : …</span>' !!}
    </div>
    <table style="margin-top: 40px;"><tr><td></td><td style="width: 40%;" class="center"><div class="bold">Pour SOGEREF Sarl</div><div class="muted small">La Direction générale</div><div style="height: 70px;"></div></td></tr></table>
@endsection

{{-- Note personnelle sur papier à en-tête SOGEREF --}}
@extends('pdf.layout')

@push('styles')
    <style>
        .note h1 { font-size: 18px; margin: 14px 0 6px; }
        .note h2 { font-size: 15px; margin: 12px 0 5px; }
        .note h3 { font-size: 12.5px; margin: 10px 0 4px; color: #1D6887; }
        .note p { margin: 0 0 7px; }
        .note ul, .note ol { margin: 0 0 8px; padding-left: 18px; }
        .note li { margin-bottom: 3px; }
        .note ul[data-type='checklist'] { list-style: none; padding-left: 2px; }
        .note li.check .chk { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #104A63; }
        .note li.done { color: #8a9dac; text-decoration: line-through; }
        .note blockquote { margin: 6px 0 10px; padding: 6px 10px; border-left: 3px solid #FFCE31; background: #fffbea; color: #3c4e5c; font-style: italic; }
        .note hr { border: 0; border-top: 1px solid #d6dfe6; margin: 12px 0; }
        .note a { color: #1D6887; }
        .note code { font-family: 'DejaVu Sans Mono', monospace; background: #f4f7f9; padding: 1px 3px; }
    </style>
@endpush

@section('content')
    <div class="section-title" style="margin-top: 0;">{{ $note->title }}</div>
    <div class="small muted" style="margin-bottom: 10px;">Note personnelle de {{ $note->user?->name }} · créée le {{ $note->created_at->format('d/m/Y') }}</div>
    <div class="note" style="font-size: 11px; line-height: 1.55;">{!! $html ?: '<p class="muted">Note vide.</p>' !!}</div>
@endsection

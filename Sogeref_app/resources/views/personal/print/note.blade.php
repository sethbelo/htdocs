@extends('personal.print.layout', ['title' => $note->title, 'heading' => 'NOTE', 'reference' => \Illuminate\Support\Str::limit($note->title, 60)])

@section('content')
    <h1 class="font-display text-[26px] font-semibold text-ink-900">{{ $note->title }}</h1>
    <p class="mb-5 text-[12px] text-ink-500">Créée le {{ $note->created_at->format('d/m/Y') }} · modifiée le {{ $note->updated_at->format('d/m/Y H:i') }}</p>
    <div class="note-prose">{!! $note->content !!}</div>
@endsection

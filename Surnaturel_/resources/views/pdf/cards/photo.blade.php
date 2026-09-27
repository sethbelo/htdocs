@if ($photo = $employee->photoDataUri())
    <img src="{{ $photo }}" style="width: {{ $pw }}mm; height: {{ $ph }}mm; border: .5mm solid {{ $border ?? '#E6007E' }};">
@else
    <div class="initials" style="width: {{ $pw }}mm; height: {{ $ph }}mm; line-height: {{ $ph }}mm; font-size: {{ $pw * 0.9 }}pt; border: .5mm solid {{ $border ?? '#E6007E' }};">{{ $employee->initials() }}</div>
@endif

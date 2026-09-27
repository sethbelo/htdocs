@if ($errors->any())
    <flux:callout variant="danger" icon="exclamation-circle" heading="Veuillez corriger les informations indiquées" text="{{ $errors->first() }}" {{ $attributes }} />
@endif

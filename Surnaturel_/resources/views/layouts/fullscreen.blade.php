<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="h-full overflow-hidden bg-zinc-100 dark:bg-zinc-950 antialiased">
    {{ $slot }}

    <flux:toast position="top end" />
    @fluxScripts
</body>
</html>

<?php
$content = file_get_contents('/Applications/MAMP/htdocs/Yeschool_app/resources/views/filament/pages/auth/login.blade.php');

$startBodyPos = strpos($content, '<body class="ys-electron-safe" x-cloak>');
$bodyContentStart = $startBodyPos + strlen('<body class="ys-electron-safe" x-cloak>');

$scriptsStartPos = strpos($content, '@livewireScripts');

$headAndBodyOpen = substr($content, 0, $bodyContentStart);
$bodyContent = substr($content, $bodyContentStart, $scriptsStartPos - $bodyContentStart);
$scriptsAndClose = substr($content, $scriptsStartPos);

$layout = $headAndBodyOpen . "\n    {{ \$slot }}\n" . $scriptsAndClose;
$login = "<div>\n" . $bodyContent . "\n</div>";

file_put_contents('/Applications/MAMP/htdocs/Yeschool_app/resources/views/filament/pages/auth/layout.blade.php', $layout);
file_put_contents('/Applications/MAMP/htdocs/Yeschool_app/resources/views/filament/pages/auth/login.blade.php', $login);

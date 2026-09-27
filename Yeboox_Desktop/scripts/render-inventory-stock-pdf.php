<?php

use App\Models\Setting;
use App\Support\DisplayCurrency;
use App\Support\DocumentPreferences;
use App\Support\InventoryStockReportData;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$report = InventoryStockReportData::build();
$currency = DisplayCurrency::CDF;
$logoPath = public_path('images/yeboox-logo.png');
$logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath)) : null;
$html = view('pdf.inventory-stock-report', [
    ...$report,
    'filters' => [],
    'company' => [
        'name' => Setting::get('company_name', 'Yeboox'),
        'address' => Setting::get('company_address', 'Kinshasa, RDC'),
        'phone' => Setting::get('company_phone', ''),
        'email' => Setting::get('company_email', ''),
        'rccm' => Setting::get('company_rccm', ''),
        'nif' => Setting::get('company_nif', ''),
    ],
    'preferences' => DocumentPreferences::report(),
    'currency' => $currency,
    'exchangeRate' => DisplayCurrency::rate(),
    'logo' => $logo,
    'generatedBy' => 'Contrôle qualité Yeboox',
])->render();

$storage = storage_path('app/dompdf');
is_dir($storage) || mkdir($storage, 0775, true);
$options = new Options([
    'defaultFont' => 'DejaVu Sans',
    'isRemoteEnabled' => false,
    'isHtml5ParserEnabled' => true,
    'isFontSubsettingEnabled' => true,
    'tempDir' => $storage,
    'fontDir' => $storage,
    'fontCache' => $storage,
    'chroot' => [public_path(), storage_path()],
]);
$pdf = new Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'landscape');
$pdf->render();
$font = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
$pdf->getCanvas()->page_text(750, 570, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.35, 0.39, 0.46]);

$target = dirname(__DIR__).'/output/pdf/etat-des-stocks.pdf';
is_dir(dirname($target)) || mkdir(dirname($target), 0775, true);
file_put_contents($target, $pdf->output());
fwrite(STDOUT, $target.PHP_EOL);

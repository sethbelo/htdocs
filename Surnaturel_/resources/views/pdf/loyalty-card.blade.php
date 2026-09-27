{{-- Carte de fidélité CR80 (85,6 × 54 mm) --}}
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #fff; }
    table { width: 100%; border-collapse: collapse; }
    .card { width: 242.65pt; height: 153.07pt; background: #16161D; position: relative; overflow: hidden; }
    .stripe td { height: 4pt; padding: 0; }
</style></head>
<body>
    <div class="card">
        <table class="stripe"><tr><td style="background: #00A6E6;"></td><td style="background: #E6007E;"></td><td style="background: #FFE500;"></td></tr></table>
        <div style="padding: 9pt 12pt 0;">
            <table>
                <tr>
                    <td style="vertical-align: top;">@if ($company['logo_light'])<img src="{{ $company['logo_light'] }}" style="height: 26pt;">@else<b>{{ $company['name'] }}</b>@endif</td>
                    <td style="text-align: right; vertical-align: top;"><span style="background: #E6007E; border-radius: 6pt; padding: 2pt 7pt; font-size: 6.5pt; font-weight: bold; letter-spacing: .6pt;">{{ mb_strtoupper($account->tier?->name ?? 'Membre') }}</span><div style="font-size: 5.8pt; color: #b5b5c8; margin-top: 3pt; letter-spacing: 1pt;">CARTE DE FIDÉLITÉ</div></td>
                </tr>
            </table>
            <table style="margin-top: 14pt;">
                <tr>
                    <td style="vertical-align: bottom;">
                        <div style="font-size: 10pt; font-weight: bold;">{{ $client->displayName() }}</div>
                        <div style="font-size: 9pt; letter-spacing: 1.6pt; margin-top: 2pt; font-family: Courier, monospace;">{{ $account->card_number }}</div>
                        <div style="font-size: 6.3pt; color: #b5b5c8; margin-top: 2pt;">Client {{ $client->number }}{{ $account->expires_at ? ' · expire '.$account->expires_at->format('m/Y') : '' }}</div>
                    </td>
                    <td style="text-align: right; width: 66pt; vertical-align: bottom;">
                        <div style="background: #fff; padding: 3pt; display: inline-block; border-radius: 3pt;"><img src="{{ \App\Services\Support\Codes::qrDataUri($account->card_number, 80) }}" style="width: 54pt; height: 54pt;"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body></html>

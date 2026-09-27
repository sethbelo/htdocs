@extends('pdf.layout')

@section('content')
    <div style="width: 242pt; height: 153pt; background: #312e81; color: white; padding: 12pt; position: relative;">
        <table>
            <tr>
                <td style="vertical-align: top;">
                    <div class="bold" style="font-size: 11pt;">{{ $company['name'] }}</div>
                    <div style="font-size: 7pt; color: #c7d2fe;">CARTE DE FIDÉLITÉ</div>
                </td>
                <td class="right" style="vertical-align: top;">
                    <span style="background: white; color: #312e81; border-radius: 6pt; padding: 2pt 6pt; font-size: 7pt; font-weight: bold;">{{ mb_strtoupper($account->tier?->name ?? 'Membre') }}</span>
                </td>
            </tr>
        </table>
        <table style="margin-top: 14pt;">
            <tr>
                <td style="vertical-align: bottom;">
                    <div class="bold" style="font-size: 10pt;">{{ $client->displayName() }}</div>
                    <div style="font-size: 8pt; letter-spacing: 1pt;">{{ $account->card_number }}</div>
                    <div style="font-size: 7pt; color: #c7d2fe;">Client {{ $client->number }}{{ $account->expires_at ? ' · Expire '.$account->expires_at->format('m/Y') : '' }}</div>
                </td>
                <td class="right" style="width: 70pt;">
                    <div style="background: white; padding: 3pt; display: inline-block;">
                        <img src="{{ \App\Services\Support\Codes::qrDataUri($account->card_number, 80) }}" style="width: 58pt; height: 58pt;">
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endsection

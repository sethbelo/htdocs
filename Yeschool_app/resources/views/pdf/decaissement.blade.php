<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de Décaissement - {{ $payment->reference }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 30px;
        }
        .header {
            border-bottom: 2px solid #6366f1;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header-title {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .header-meta {
            float: right;
            text-align: right;
            font-size: 11px;
            color: #666;
            margin-top: -30px;
        }
        .clear {
            clear: both;
        }
        .voucher-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 30px 0 20px 0;
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .details-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .details-table td.label {
            font-weight: bold;
            color: #555;
            width: 30%;
            background-color: #f9fafb;
        }
        .amount-highlight {
            font-size: 16px;
            font-weight: bold;
            color: #dc2626;
        }
        .signatures-container {
            margin-top: 60px;
            width: 100%;
        }
        .signature-box {
            float: left;
            width: 45%;
            text-align: center;
            border-top: 1px dashed #999;
            padding-top: 10px;
            font-size: 12px;
        }
        .signature-box.right {
            float: right;
        }
        .footer {
            margin-top: 100px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="header-title">Yeschool</h1>
        <div class="header-meta">
            <strong>Date :</strong> {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : date('d/m/Y') }}<br>
            <strong>Référence :</strong> {{ $payment->reference }}
        </div>
        <div class="clear"></div>
    </div>

    <div class="voucher-title">
        Bon de Décaissement
    </div>

    <table class="details-table">
        <tr>
            <td class="label">Bénéficiaire</td>
            <td>
                @if($payment->teacher)
                    {{ $payment->teacher->first_name }} {{ $payment->teacher->last_name }} (Enseignant)
                @else
                    {{ $payment->beneficiary_name ?? 'N/A' }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Motif du Décaissement</td>
            <td><strong>{{ $payment->motif ?? 'Autre' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Description / Détails</td>
            <td>{{ $payment->description ?? 'Aucune description fournie.' }}</td>
        </tr>
        <tr>
            <td class="label">Montant</td>
            <td class="amount-highlight">
                ${{ number_format($payment->amount, 2) }} USD
                <br>
                <span style="font-size: 12px; color: #666; font-weight: normal;">
                    Soit approx. {{ number_format($payment->amount * 2500, 0, ',', ' ') }} FC (Taux de change indicatif : 2500)
                </span>
            </td>
        </tr>
        <tr>
            <td class="label">Moyen de Règlement</td>
            <td>{{ $payment->payment_method ?? 'Cash' }}</td>
        </tr>
        <tr>
            <td class="label">Validation de la Direction</td>
            <td>
                @if($payment->status === 'approved')
                    <span style="color: #059669; font-weight: bold;">✔ ACCORDÉ & VALIDÉ par le Super Administrateur</span>
                @else
                    <span style="color: #d97706; font-weight: bold;">⚠ EN ATTENTE DE VALIDATION</span>
                @endif
            </td>
        </tr>
    </table>

    <div class="signatures-container">
        <div class="signature-box">
            Le Comptable / Caissier
            <br><br><br><br>
            (Signature et cachet)
        </div>
        <div class="signature-box right">
            Le Bénéficiaire
            <br><br><br><br>
            (Signature et nom)
        </div>
        <div class="clear"></div>
    </div>

    <div class="footer">
        Document officiel généré par Yeschool App. Classe OHADA 6 - Charges (Décaissements).
    </div>

</body>
</html>

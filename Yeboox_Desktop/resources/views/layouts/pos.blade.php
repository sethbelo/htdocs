<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Point de vente' }} · {{ \App\Models\Setting::get('company_name', 'Yeboox') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
    <style>
        .pos-addition[data-receipt-template="compact"], .pos-addition[data-receipt-template="eco"] { padding: 10px !important; letter-spacing: -.02em; }
        .pos-addition[data-receipt-template="modern"] { border: 3px solid #18181b; }
        .pos-addition[data-receipt-template="modern"] .pos-ticket-header { border-bottom: 5px solid #18181b; padding-bottom: 8px; text-align: left; }
        .pos-addition[data-receipt-template="elegant"], .pos-addition[data-receipt-template="premium"] { border-block: 3px double #18181b; }
        .pos-addition[data-receipt-template="boxed"], .pos-addition[data-receipt-template="merchant"] { border: 3px double #18181b; }
        .pos-addition[data-receipt-template="minimal"] { box-shadow: none !important; }
        .pos-addition[data-receipt-template="bold"] { border: 4px solid #18181b; }
        .pos-addition[data-receipt-template="bold"] .pos-ticket-total { border-width: 5px; font-size: 1.45em; }
        .pos-addition[data-receipt-template="friendly"] { border: 2px dashed #18181b; border-radius: 18px; }
        .pos-addition[data-receipt-template="fiscal"], .pos-addition[data-receipt-template="vat_summary"] { border-top: 7px solid #18181b; }
        .pos-addition[data-receipt-template="transaction"] .pos-ticket-meta { border: 1px solid #18181b; padding: 8px; }
        .pos-addition[data-receipt-template="playlist"] .pos-ticket-items, .pos-addition[data-receipt-template="ledger_receipt"] .pos-ticket-items { border-block-style: double; border-block-width: 3px; }
        .pos-addition[data-receipt-template="timestamp"] .pos-ticket-meta { border-left: 5px solid #18181b; padding-left: 9px; }
        .pos-addition[data-receipt-template="loyalty"] .pos-ticket-footer { border: 2px dashed #18181b; padding: 9px; font-weight: 700; }
        @media print { html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; } }
    </style>
</head>
<body class="h-dvh overflow-hidden bg-zinc-100 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
    <flux:accent color="violet" />
    {{ $slot }}
    <x-logout-confirmation />
    @livewireScripts
    @fluxScripts
    <x-display-runtime />
    <script>
        let lastAutomaticallyPrintedSale = null;

        const printReceiptWhenReady = async (saleId, attempt = 0) => {
            if (saleId && lastAutomaticallyPrintedSale === saleId) return;

            const ticket = document.getElementById('pos-receipt-print');
            if (!ticket) {
                if (attempt < 20) window.setTimeout(() => printReceiptWhenReady(saleId, attempt + 1), 100);
                return;
            }

            if (document.fonts?.ready) await document.fonts.ready;
            if (saleId) lastAutomaticallyPrintedSale = saleId;

            const result = await window.printPosReceipt(true);
            if (!result?.success && !result?.canceled) {
                console.error('Impression automatique du ticket impossible:', result?.reason ?? 'Erreur inconnue');
            }
        };

        document.addEventListener('livewire:init', () => {
            Livewire.on('pos-electron-sync', async (event) => {
                if (!window.yebooxDesktop?.isElectron) return;
                const payload = Array.isArray(event) ? event[0] : event;
                try {
                    await window.yebooxDesktop.savePosState(payload?.state ?? payload ?? {});
                } catch (error) {
                    console.error('Synchronisation POS Electron impossible', error);
                }
            });
            Livewire.on('pos-receipt-ready', (event = {}) => {
                if (!window.yebooxDesktop?.isElectron) return;
                const payload = Array.isArray(event) ? (event[0] ?? {}) : event;
                window.setTimeout(() => printReceiptWhenReady(payload?.saleId ?? null), 100);
            });
        });

        window.printPosDocument = async (elementId, silent = false) => {
            const ticket = document.getElementById(elementId);
            if (!ticket) return;
            const paperWidth = Number(ticket.dataset.paperWidth || 80);
            const contentHeightMm = Math.ceil(ticket.getBoundingClientRect().height / 96 * 25.4) + 4;
            const styles = Array.from(document.head.querySelectorAll('style, link[rel="stylesheet"]')).map(node => {
                if (node.tagName !== 'LINK') return node.outerHTML;
                const clone = node.cloneNode(true);
                clone.href = node.href;
                return clone.outerHTML;
            }).join('\n');
            const payload = { paperWidth, contentHeightMm, html: ticket.outerHTML, styles, silent };

            if (window.yebooxDesktop?.isElectron) {
                let result = await window.yebooxDesktop.printReceipt(payload);
                if (silent && !result?.success) {
                    result = await window.yebooxDesktop.printReceipt({ ...payload, silent: false });
                }
                if (!result?.success && !result?.canceled) console.error('Impression thermique impossible:', result?.reason);
                return result;
            }

            const printWindow = window.open('', '_blank', 'popup,width=420,height=720');
            if (!printWindow) return { success: false, reason: 'Fenêtre d’impression bloquée.' };
            printWindow.document.open();
            printWindow.document.write(`<!doctype html><html><head><meta charset="utf-8">${styles}<style>@page{size:${paperWidth}mm auto;margin:0}html,body{margin:0;padding:0;width:${paperWidth}mm;background:#fff;color:#000}article{box-shadow:none!important;max-width:none!important}</style></head><body>${ticket.outerHTML}</body></html>`);
            printWindow.document.close();
            printWindow.addEventListener('load', () => { printWindow.focus(); printWindow.print(); printWindow.close(); }, { once: true });
            return { success: true, fallback: true };
        };
        window.printPosAddition = () => window.printPosDocument('pos-addition-print');
        window.printPosReceipt = (silent = false) => window.printPosDocument('pos-receipt-print', silent);
    </script>
</body>
</html>

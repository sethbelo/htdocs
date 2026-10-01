/* =============================================================================
   SOGEREF ERP — Communication & marketing (composants Alpine)
   -----------------------------------------------------------------------------
     1. smsCounter : compteur de caractères / segments SMS (GSM 7 bits ou Unicode)
     2. scrollBottom : garde un fil de discussion collé en bas à chaque rendu
   ============================================================================= */

// Alphabet GSM 03.38 (caractères simples) et extension (comptés double).
const GSM_BASIC = '@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';
const GSM_EXTENDED = '^{}\\[~]|€\f';

export function smsStats(text) {
    const value = String(text ?? '');
    let units = 0;
    let unicode = false;
    for (const ch of value) {
        if (GSM_BASIC.includes(ch)) units += 1;
        else if (GSM_EXTENDED.includes(ch)) units += 2;
        else { unicode = true; break; }
    }
    if (unicode) units = [...value].length;
    const single = unicode ? 70 : 160;
    const multi = unicode ? 67 : 153;
    const segments = units === 0 ? 0 : (units <= single ? 1 : Math.ceil(units / multi));
    const capacity = segments <= 1 ? single : segments * multi;

    return { length: units, segments, remaining: Math.max(0, capacity - units), unicode };
}

document.addEventListener('alpine:init', () => {
    // <div x-data="smsCounter(@entangle('body'))"> … <span x-text="label"></span>
    window.Alpine.data('smsCounter', (model) => ({
        text: model,
        get stats() { return smsStats(this.text); },
        get label() {
            const s = this.stats;
            if (!s.length) return '0 / 160';
            return `${s.length} car. · ${s.segments} SMS · reste ${s.remaining}${s.unicode ? ' · Unicode' : ''}`;
        },
    }));

    // <div x-data="scrollBottom"> : défile en bas au montage et après chaque mise à jour Livewire.
    window.Alpine.data('scrollBottom', () => ({
        init() {
            const stick = () => this.$nextTick(() => { this.$el.scrollTop = this.$el.scrollHeight; });
            stick();
            new MutationObserver(stick).observe(this.$el, { childList: true, subtree: true });
        },
    }));
});

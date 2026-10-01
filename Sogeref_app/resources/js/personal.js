/* =============================================================================
   SOGEREF ERP — Espace personnel (composants Alpine)
   -----------------------------------------------------------------------------
     1. noteEditor   : éditeur riche « façon Notion » (contenteditable + barre)
     2. sheetEditor  : tableur (grille, navigation clavier, formules)
     3. sortableList : réordonnancement glisser-déposer (tâches du jour)
   Le moteur de formules est le miroir de app/Support/SheetFormula.php.
   ============================================================================= */
import Sortable from 'sortablejs';

/* ─── Moteur de formules ─────────────────────────────────────────────────── */
const FUNCTIONS = { SUM: 'SUM', SOMME: 'SUM', AVERAGE: 'AVERAGE', MOYENNE: 'AVERAGE', MIN: 'MIN', MAX: 'MAX', COUNT: 'COUNT', NB: 'COUNT' };
const REF_RE = /^\$?[A-Za-z]+\$?\d+$/;

export function columnName(index) {
    let name = '';
    for (let n = index + 1; n > 0; n = Math.floor((n - 1) / 26)) name = String.fromCharCode(65 + ((n - 1) % 26)) + name;
    return name;
}

export function parseRef(ref) {
    const m = /^\$?([A-Z]+)\$?(\d+)$/i.exec(ref);
    if (!m) throw new Error('#REF!');
    let col = 0;
    for (const ch of m[1].toUpperCase()) col = col * 26 + (ch.charCodeAt(0) - 64);
    return [col - 1, Number(m[2]) - 1];
}

export function toNumber(raw) {
    const clean = String(raw).trim().replace(/[\s  ]/g, '');
    return /^[-+]?\d+(?:[.,]\d+)?(?:[eE][+-]?\d+)?$/.test(clean) ? Number(clean.replace(',', '.')) : null;
}

export function formatNumber(value) {
    if (!Number.isFinite(value)) return '#VALUE!';
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 6 }).format(value);
}

export function evaluateSheet(cells) {
    const cache = {};
    const stack = [];
    const isError = (v) => typeof v === 'string' && v.startsWith('#');

    const value = (ref) => {
        ref = ref.replace(/\$/g, '').toUpperCase();
        if (ref in cache) return cache[ref];
        if (stack.includes(ref)) return '#REF!';
        const raw = String(cells[ref] ?? '').trim();
        if (raw === '') return (cache[ref] = '');
        if (!raw.startsWith('=')) {
            const n = toNumber(raw);
            return (cache[ref] = n ?? raw);
        }
        stack.push(ref);
        try {
            return (cache[ref] = compute(raw.slice(1)));
        } finally {
            stack.pop();
        }
    };

    const compute = (expression) => {
        let tokens;
        let pos = 0;
        const peek = () => tokens[pos];
        const expect = (t) => { if (peek() !== t) throw new Error('#ERR!'); pos++; };
        const arith = (a, b, op) => {
            for (const v of [a, b]) if (typeof v === 'string') return isError(v) ? v : '#VALUE!';
            if (op === '/' && b === 0) return '#DIV/0!';
            return op === '+' ? a + b : op === '-' ? a - b : op === '*' ? a * b : a / b;
        };
        const postfix = (v) => {
            if (peek() === '%') { pos++; return typeof v === 'number' ? v / 100 : '#VALUE!'; }
            return v;
        };
        const range = (from, to) => {
            const [c1, r1] = parseRef(from);
            const [c2, r2] = parseRef(to);
            const refs = [];
            for (let r = Math.min(r1, r2); r <= Math.max(r1, r2); r++) for (let c = Math.min(c1, c2); c <= Math.max(c1, c2); c++) refs.push(columnName(c) + (r + 1));
            return refs;
        };
        const call = (fn) => {
            const numbers = [];
            let error = null;
            if (peek() !== ')') {
                do {
                    if (peek() === ',' || peek() === ';') pos++;
                    const t = peek();
                    if (t && REF_RE.test(t) && tokens[pos + 1] === ':') {
                        const end = tokens[pos + 2];
                        if (!end) throw new Error('#ERR!');
                        pos += 3;
                        for (const ref of range(t, end)) {
                            const v = value(ref);
                            if (isError(v)) error ??= v;
                            else if (typeof v === 'number') numbers.push(v);
                        }
                    } else {
                        const v = expr();
                        if (isError(v)) error ??= v;
                        else if (typeof v === 'number') numbers.push(v);
                    }
                } while (peek() === ',' || peek() === ';');
            }
            expect(')');
            if (error) return error;
            const sum = numbers.reduce((s, n) => s + n, 0);
            switch (fn) {
                case 'SUM': return sum;
                case 'AVERAGE': return numbers.length ? sum / numbers.length : '#DIV/0!';
                case 'MIN': return numbers.length ? Math.min(...numbers) : 0;
                case 'MAX': return numbers.length ? Math.max(...numbers) : 0;
                default: return numbers.length;
            }
        };
        const factor = () => {
            const t = peek();
            if (t === undefined) throw new Error('#ERR!');
            if (t === '-' || t === '+') { pos++; return arith(0, factor(), t); }
            if (t === '(') { pos++; const v = expr(); expect(')'); return postfix(v); }
            if (/^\d/.test(t)) { pos++; return postfix(Number(t.replace(',', '.'))); }
            if (REF_RE.test(t) && tokens[pos + 1] !== '(') { pos++; const v = value(t); return v === '' ? 0 : postfix(v); }
            const name = t.toUpperCase();
            if (FUNCTIONS[name] && tokens[pos + 1] === '(') { pos += 2; return postfix(call(FUNCTIONS[name])); }
            throw new Error('#ERR!');
        };
        const term = () => {
            let left = factor();
            while (peek() === '*' || peek() === '/') { const op = tokens[pos++]; left = arith(left, factor(), op); }
            return left;
        };
        const expr = () => {
            let left = term();
            while (peek() === '+' || peek() === '-') { const op = tokens[pos++]; left = arith(left, term(), op); }
            return left;
        };

        try {
            const re = /\s*(\$?[A-Za-z]+\$?\d+|[A-Za-z][A-Za-z.]*|\d+(?:[.,]\d+)?(?:[eE][+-]?\d+)?|[-+*/():;,%^])/y;
            tokens = [];
            let consumed = '';
            let m;
            while ((m = re.exec(expression)) !== null) { tokens.push(m[1]); consumed += m[1]; if (re.lastIndex >= expression.length) break; }
            if (consumed !== expression.replace(/\s+/g, '')) throw new Error('#ERR!');
            const result = expr();
            if (pos < tokens.length) throw new Error('#ERR!');
            if (typeof result === 'string') return result;
            return Number.isFinite(result) ? Math.round(result * 1e10) / 1e10 : '#DIV/0!';
        } catch (e) {
            return String(e.message).startsWith('#') ? e.message : '#ERR!';
        }
    };

    const values = {};
    for (const ref of Object.keys(cells)) if (String(cells[ref] ?? '') !== '') values[ref.toUpperCase()] = value(ref);
    return values;
}

window.SogerefSheet = { evaluateSheet, columnName, parseRef, formatNumber };

document.addEventListener('alpine:init', () => {
    /* ─── 1. ÉDITEUR DE NOTES ───────────────────────────────────────────────── */
    // <div x-data="noteEditor({ title, autosave: true })"> … <div x-ref="editor" contenteditable>
    Alpine.data('noteEditor', (options = {}) => ({
        title: options.title ?? '',
        state: 'saved', // saved | dirty | saving
        timer: null,
        active: {},
        pinned: false,
        init() {
            const editor = this.$refs.editor;
            document.execCommand('defaultParagraphSeparator', false, 'p');
            if (!editor.innerHTML.trim()) editor.innerHTML = '<p><br></p>';
            editor.addEventListener('input', (e) => { this.markdown(e); this.changed(); });
            editor.addEventListener('keydown', (e) => this.shortcuts(e));
            editor.addEventListener('click', (e) => this.toggleCheck(e));
            editor.addEventListener('paste', (e) => {
                // Collage en texte brut pour éviter d'importer des styles externes.
                e.preventDefault();
                document.execCommand('insertText', false, (e.clipboardData || window.clipboardData).getData('text/plain'));
            });
            this._sel = () => this.refreshActive();
            document.addEventListener('selectionchange', this._sel);
            this._unload = (e) => { if (this.state !== 'saved') { e.preventDefault(); e.returnValue = ''; } };
            window.addEventListener('beforeunload', this._unload);
        },
        destroy() {
            document.removeEventListener('selectionchange', this._sel);
            window.removeEventListener('beforeunload', this._unload);
            if (this.state === 'dirty') this.save();
        },
        changed() {
            this.state = 'dirty';
            clearTimeout(this.timer);
            if (options.autosave !== false) this.timer = setTimeout(() => this.save(), 1200);
        },
        async save() {
            clearTimeout(this.timer);
            this.state = 'saving';
            try {
                await this.$wire.saveNote(options.id, this.title, this.$refs.editor.innerHTML);
                this.state = 'saved';
            } catch {
                this.state = 'dirty';
            }
        },
        /** Enregistre puis ouvre une impression / un export dans un nouvel onglet. */
        openAfterSave(url) {
            const win = window.open('', '_blank');
            this.save().then(() => { if (win) win.location = url; else window.location = url; });
        },
        focus() { this.$refs.editor.focus(); },
        exec(command, value = null) {
            this.focus();
            document.execCommand(command, false, value);
            this.changed();
            this.refreshActive();
        },
        block(tag) {
            this.focus();
            const current = (document.queryCommandValue('formatBlock') || '').toLowerCase();
            document.execCommand('formatBlock', false, current === tag ? 'p' : tag);
            this.changed();
            this.refreshActive();
        },
        link() {
            const url = prompt('Adresse du lien (https://…)', 'https://');
            if (url && /^(https?:\/\/|mailto:|tel:)/i.test(url)) this.exec('createLink', url);
        },
        divider() {
            this.exec('insertHorizontalRule');
            this.exec('formatBlock', 'p');
        },
        checklist() {
            this.focus();
            const list = this.closest('ul');
            if (list && list.dataset.type === 'checklist') {
                document.execCommand('insertUnorderedList');
            } else {
                if (!list) document.execCommand('insertUnorderedList');
                const ul = this.closest('ul');
                if (ul) {
                    ul.dataset.type = 'checklist';
                    ul.querySelectorAll(':scope > li').forEach(li => { if (!li.dataset.checked) li.dataset.checked = 'false'; });
                }
            }
            this.changed();
            this.refreshActive();
        },
        closest(tag) {
            const sel = window.getSelection();
            if (!sel.rangeCount) return null;
            let node = sel.anchorNode;
            while (node && node !== this.$refs.editor) {
                if (node.nodeType === 1 && node.tagName.toLowerCase() === tag) return node;
                node = node.parentNode;
            }
            return null;
        },
        toggleCheck(e) {
            const li = e.target.closest?.('ul[data-type="checklist"] > li');
            if (!li) return;
            const box = li.getBoundingClientRect();
            if (e.clientX - box.left > 26) return; // clic sur la case uniquement
            e.preventDefault();
            li.dataset.checked = li.dataset.checked === 'true' ? 'false' : 'true';
            this.changed();
        },
        shortcuts(e) {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') { e.preventDefault(); this.save(); return; }
            // Nouvelle ligne d'une checklist : la case repart décochée.
            // Entrée dans une citation : on en sort (nouveau paragraphe dessous).
            if (e.key === 'Enter' && !e.shiftKey && this.closest('blockquote') && !this.closest('li')) {
                e.preventDefault();
                const quote = this.closest('blockquote');
                const p = document.createElement('p');
                p.appendChild(document.createElement('br'));
                quote.after(p);
                const r = document.createRange();
                r.setStart(p, 0);
                r.collapse(true);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(r);
                this.changed();
                this.refreshActive();
                return;
            }
            if (e.key === 'Enter' && !e.shiftKey) {
                const li = this.closest('li');
                if (li && li.parentNode?.dataset?.type === 'checklist') {
                    setTimeout(() => { const next = this.closest('li'); if (next && next !== li) next.dataset.checked = 'false'; }, 0);
                }
            }
        },
        /** Raccourcis Markdown en début de bloc : #, ##, ###, -, *, 1., [], >, --- suivis d'une espace. */
        markdown(e) {
            if (e.inputType !== 'insertText' || e.data !== ' ') return;
            const sel = window.getSelection();
            if (!sel.rangeCount || !sel.isCollapsed) return;
            const node = sel.anchorNode;
            if (!node || node.nodeType !== 3) return;
            const typed = node.textContent.slice(0, sel.anchorOffset);
            const marker = typed.replace(/[\s\u00A0]$/, '');
            const actions = {
                '#': () => document.execCommand('formatBlock', false, 'h1'),
                '##': () => document.execCommand('formatBlock', false, 'h2'),
                '###': () => document.execCommand('formatBlock', false, 'h3'),
                '-': () => document.execCommand('insertUnorderedList'),
                '*': () => document.execCommand('insertUnorderedList'),
                '1.': () => document.execCommand('insertOrderedList'),
                '[]': () => this.checklist(),
                '>': () => document.execCommand('formatBlock', false, 'blockquote'),
                '---': () => document.execCommand('insertHorizontalRule'),
            };
            if (!(marker in actions) || node !== this.firstText(node.parentNode.closest('p, h1, h2, h3, li, blockquote, div') || node.parentNode)) return;
            const range = document.createRange();
            range.setStart(node, 0);
            range.setEnd(node, sel.anchorOffset);
            range.deleteContents();
            if (!node.textContent) {
                // Bloc vide : on garde un <br> pour que le curseur reste en place.
                const br = document.createElement('br');
                node.parentNode.replaceChild(br, node);
                const r = document.createRange();
                r.setStartBefore(br);
                r.collapse(true);
                sel.removeAllRanges();
                sel.addRange(r);
            }
            actions[marker]();
            this.refreshActive();
        },
        firstText(el) {
            const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
            return walker.nextNode();
        },
        refreshActive() {
            if (!this.$refs.editor.contains(window.getSelection().anchorNode)) return;
            const block = (document.queryCommandValue('formatBlock') || '').toLowerCase();
            const list = this.closest('ul');
            this.active = {
                bold: document.queryCommandState('bold'),
                italic: document.queryCommandState('italic'),
                underline: document.queryCommandState('underline'),
                strike: document.queryCommandState('strikeThrough'),
                h1: block === 'h1', h2: block === 'h2', h3: block === 'h3', quote: block === 'blockquote',
                ul: !!list && list.dataset.type !== 'checklist',
                checklist: !!list && list.dataset.type === 'checklist',
                ol: !!this.closest('ol'),
            };
        },
    }));

    /* ─── 2. TABLEUR ────────────────────────────────────────────────────────── */
    Alpine.data('sheetEditor', (initial = {}) => ({
        title: initial.title ?? '',
        rows: initial.rows ?? 20,
        cols: initial.cols ?? 8,
        cells: initial.cells && !Array.isArray(initial.cells) ? { ...initial.cells } : {},
        values: {},
        sel: { r: 0, c: 0 },
        editing: false,
        draft: '',
        state: 'saved',
        timer: null,
        maxRows: initial.maxRows ?? 500,
        maxCols: initial.maxCols ?? 52,
        init() {
            this.recalc();
            this._unload = (e) => { if (this.state !== 'saved') { e.preventDefault(); e.returnValue = ''; } };
            window.addEventListener('beforeunload', this._unload);
        },
        destroy() { window.removeEventListener('beforeunload', this._unload); if (this.state === 'dirty') this.save(); },
        openAfterSave(url) {
            const win = window.open('', '_blank');
            this.save().then(() => { if (win) win.location = url; else window.location = url; });
        },
        col(i) { return columnName(i); },
        ref(r, c) { return columnName(c) + (r + 1); },
        get selectedRef() { return this.ref(this.sel.r, this.sel.c); },
        get formulaBar() { return this.editing ? this.draft : (this.cells[this.selectedRef] ?? ''); },
        set formulaBar(v) { this.editing = true; this.draft = v; },
        raw(r, c) { return this.cells[this.ref(r, c)] ?? ''; },
        display(r, c) {
            const v = this.values[this.ref(r, c)];
            if (v === undefined || v === '') return '';
            return typeof v === 'number' ? formatNumber(v) : v;
        },
        isNumber(r, c) { return typeof this.values[this.ref(r, c)] === 'number'; },
        isError(r, c) { const v = this.values[this.ref(r, c)]; return typeof v === 'string' && v.startsWith('#'); },
        recalc() { this.values = evaluateSheet(this.cells); },
        select(r, c) {
            if (this.editing) this.commit();
            this.sel = { r: Math.max(0, Math.min(this.rows - 1, r)), c: Math.max(0, Math.min(this.cols - 1, c)) };
            this.$nextTick(() => this.$refs.grid?.querySelector(`[data-ref="${this.selectedRef}"]`)?.scrollIntoView({ block: 'nearest', inline: 'nearest' }));
            this.$refs.grid?.focus({ preventScroll: true });
        },
        /** Clic : sélectionne ; second clic sur la cellule active → édition (utile au tactile). */
        clickCell(r, c) {
            if (!this.editing && this.sel.r === r && this.sel.c === c) { this.startEdit(); return; }
            this.select(r, c);
        },
        startEdit(initialText = null) {
            this.editing = true;
            this.draft = initialText ?? (this.cells[this.selectedRef] ?? '');
            // Champ d'édition unique superposé à la cellule : affiché et focalisé immédiatement.
            const input = this.$refs.cellInput;
            const td = this.$refs.grid.querySelector(`[data-ref="${this.selectedRef}"]`);
            if (!input || !td) return;
            const wrap = this.$refs.grid.getBoundingClientRect();
            const cell = td.getBoundingClientRect();
            Object.assign(input.style, {
                display: 'block',
                left: `${cell.left - wrap.left + this.$refs.grid.scrollLeft}px`,
                top: `${cell.top - wrap.top + this.$refs.grid.scrollTop}px`,
                width: `${Math.max(cell.width, 140)}px`,
                height: `${cell.height}px`,
            });
            input.value = this.draft;
            input.focus({ preventScroll: true });
            input.setSelectionRange(input.value.length, input.value.length);
        },
        hideInput() {
            if (this.$refs.cellInput) this.$refs.cellInput.style.display = 'none';
        },
        commit() {
            if (!this.editing) return;
            const ref = this.selectedRef;
            const value = String(this.draft ?? '');
            if (value === '') delete this.cells[ref]; else this.cells[ref] = value;
            this.editing = false;
            this.hideInput();
            this.recalc();
            this.changed();
        },
        cancel() { this.editing = false; this.draft = ''; this.hideInput(); this.$refs.grid.focus(); },
        clearCell() { delete this.cells[this.selectedRef]; this.recalc(); this.changed(); },
        onGridKey(e) {
            if (this.editing) return;
            const k = e.key;
            const moves = { ArrowUp: [-1, 0], ArrowDown: [1, 0], ArrowLeft: [0, -1], ArrowRight: [0, 1] };
            if (moves[k]) { e.preventDefault(); this.select(this.sel.r + moves[k][0], this.sel.c + moves[k][1]); return; }
            if (k === 'Tab') { e.preventDefault(); this.select(this.sel.r, this.sel.c + (e.shiftKey ? -1 : 1)); return; }
            if (k === 'Enter' || k === 'F2') { e.preventDefault(); this.startEdit(); return; }
            if (k === 'Delete' || k === 'Backspace') { e.preventDefault(); this.clearCell(); return; }
            if ((e.metaKey || e.ctrlKey) && k.toLowerCase() === 's') { e.preventDefault(); this.save(); return; }
            if (k.length === 1 && !e.metaKey && !e.ctrlKey && !e.altKey) { e.preventDefault(); this.startEdit(k); }
        },
        onEditKey(e) {
            if (e.key === 'Enter') { e.preventDefault(); this.commit(); this.select(this.sel.r + (e.shiftKey ? -1 : 1), this.sel.c); }
            else if (e.key === 'Tab') { e.preventDefault(); this.commit(); this.select(this.sel.r, this.sel.c + (e.shiftKey ? -1 : 1)); }
            else if (e.key === 'Escape') { e.preventDefault(); this.cancel(); }
        },
        shift(axis, at, delta) {
            // Décale les cellules d'une ligne/colonne (insertion +1, suppression -1)
            // et réécrit les références des formules, comme un tableur classique.
            const move = (idx) => (delta > 0 ? (idx >= at ? idx + 1 : idx) : (idx > at ? idx - 1 : idx));
            const rewrite = (formula) => formula.replace(/(\$?)([A-Za-z]{1,2})(\$?)(\d{1,4})(?![A-Za-z0-9(])/g, (m, d1, letters, d2, digits) => {
                let [c, r] = parseRef(letters + digits);
                if (axis === 'row') r = move(r); else c = move(c);
                return `${d1}${columnName(c)}${d2}${r + 1}`;
            });
            const next = {};
            for (const [ref, v] of Object.entries(this.cells)) {
                const [c, r] = parseRef(ref);
                const idx = axis === 'row' ? r : c;
                if (delta < 0 && idx === at) continue;
                const moved = move(idx);
                const value = typeof v === 'string' && v.startsWith('=') ? rewrite(v) : v;
                next[axis === 'row' ? columnName(c) + (moved + 1) : columnName(moved) + (r + 1)] = value;
            }
            this.cells = next;
        },
        addRow(after = true) {
            if (this.rows >= this.maxRows) return;
            this.commit();
            const at = this.sel.r + (after ? 1 : 0);
            this.shift('row', at, 1);
            this.rows++;
            this.sel.r = at;
            this.recalc(); this.changed();
        },
        addCol(after = true) {
            if (this.cols >= this.maxCols) return;
            this.commit();
            const at = this.sel.c + (after ? 1 : 0);
            this.shift('col', at, 1);
            this.cols++;
            this.sel.c = at;
            this.recalc(); this.changed();
        },
        removeRow() {
            if (this.rows <= 1) return;
            this.commit();
            this.shift('row', this.sel.r, -1);
            this.rows--;
            this.sel.r = Math.min(this.sel.r, this.rows - 1);
            this.recalc(); this.changed();
        },
        removeCol() {
            if (this.cols <= 1) return;
            this.commit();
            this.shift('col', this.sel.c, -1);
            this.cols--;
            this.sel.c = Math.min(this.sel.c, this.cols - 1);
            this.recalc(); this.changed();
        },
        insertFunction(fn) {
            const ref = this.selectedRef;
            const startRow = Math.max(0, this.sel.r - 1);
            let top = startRow;
            while (top > 0 && this.isNumber(top - 1, this.sel.c)) top--;
            const range = this.sel.r > 0 ? `${columnName(this.sel.c)}${top + 1}:${columnName(this.sel.c)}${startRow + 1}` : '';
            this.startEdit(`=${fn}(${range})`);
            return ref;
        },
        changed() {
            this.state = 'dirty';
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.save(), 1500);
        },
        async save() {
            clearTimeout(this.timer);
            if (this.editing) this.commit();
            this.state = 'saving';
            try {
                await this.$wire.saveSheet(initial.id, this.title, this.rows, this.cols, JSON.parse(JSON.stringify(this.cells)));
                this.state = 'saved';
            } catch {
                this.state = 'dirty';
            }
        },
    }));

    /* ─── 3. LISTE TRIABLE ──────────────────────────────────────────────────── */
    // <ul x-data="sortableList('reorder')"> <li data-id="12"> … <span data-handle>
    Alpine.data('sortableList', (method = 'reorder') => ({
        init() {
            Sortable.create(this.$root, {
                animation: 160,
                handle: '[data-handle]',
                ghostClass: 'sortable-ghost',
                onEnd: () => this.$wire.call(method, [...this.$root.querySelectorAll(':scope > [data-id]')].map(el => Number(el.dataset.id))),
            });
        },
    }));
});

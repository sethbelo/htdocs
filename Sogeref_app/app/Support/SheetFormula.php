<?php

namespace App\Support;

use RuntimeException;

/**
 * Moteur de formules des tableurs personnels (miroir de resources/js/personal.js).
 *
 *   =SUM(A1:A5) · =AVERAGE(…) · =MIN(…) · =MAX(…) · =COUNT(…)
 *   opérateurs + - * / , parenthèses, références de cellules (A1, $B$2) ;
 *   alias français acceptés : SOMME, MOYENNE, NB. Séparateur « , » ou « ; ».
 *
 * Erreurs : #REF! (référence circulaire), #DIV/0!, #VALUE!, #ERR! (syntaxe).
 */
final class SheetFormula
{
    private const FUNCTIONS = ['SUM' => 'SUM', 'SOMME' => 'SUM', 'AVERAGE' => 'AVERAGE', 'MOYENNE' => 'AVERAGE', 'MIN' => 'MIN', 'MAX' => 'MAX', 'COUNT' => 'COUNT', 'NB' => 'COUNT'];

    private array $cells;

    private array $cache = [];

    private array $stack = [];

    private array $tokens = [];

    private int $pos = 0;

    private function __construct(array $cells)
    {
        $this->cells = array_change_key_case($cells, CASE_UPPER);
    }

    /** Calcule toutes les cellules : [ref => valeur (float|string)]. Seules les formules et nombres sont renvoyés. */
    public static function evaluateAll(array $cells): array
    {
        $engine = new self($cells);
        $values = [];
        foreach ($engine->cells as $ref => $raw) {
            $raw = (string) $raw;
            if ($raw === '') {
                continue;
            }
            $value = $engine->value($ref);
            if (is_float($value) || str_starts_with($raw, '=')) {
                $values[$ref] = $value;
            }
        }

        return $values;
    }

    /** Évalue une seule formule dans le contexte d'une grille. */
    public static function evaluate(string $formula, array $cells = []): float|string
    {
        return (new self($cells))->compute(ltrim($formula, '='));
    }

    public static function format(float|int $value): string
    {
        if (is_float($value) && (is_nan($value) || is_infinite($value))) {
            return '#VALUE!';
        }

        return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.') ?: '0';
    }

    public static function column(int $index): string
    {
        $name = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $name = chr(65 + ($n - 1) % 26).$name;
        }

        return $name;
    }

    public static function ref(int $col, int $row): string
    {
        return self::column($col).($row + 1);
    }

    /** "B3" → [1, 2] (colonne, ligne — base 0). */
    public static function parseRef(string $ref): array
    {
        if (! preg_match('/^\$?([A-Z]+)\$?(\d+)$/i', $ref, $m)) {
            throw new RuntimeException('#REF!');
        }
        $col = 0;
        foreach (str_split(strtoupper($m[1])) as $char) {
            $col = $col * 26 + (ord($char) - 64);
        }

        return [$col - 1, (int) $m[2] - 1];
    }

    // ── Évaluation ────────────────────────────────────────────────────────
    private function value(string $ref): float|string
    {
        $ref = strtoupper(str_replace('$', '', $ref));
        if (array_key_exists($ref, $this->cache)) {
            return $this->cache[$ref];
        }
        if (in_array($ref, $this->stack, true)) {
            return '#REF!';
        }
        $raw = trim((string) ($this->cells[$ref] ?? ''));
        if ($raw === '') {
            return $this->cache[$ref] = '';
        }
        if (! str_starts_with($raw, '=')) {
            $number = self::toNumber($raw);

            return $this->cache[$ref] = $number ?? $raw;
        }

        $this->stack[] = $ref;
        try {
            $result = (new self($this->cells))->withShared($this)->compute(substr($raw, 1));
        } finally {
            array_pop($this->stack);
        }

        return $this->cache[$ref] = $result;
    }

    private function withShared(self $parent): self
    {
        $this->cache = &$parent->cache;
        $this->stack = &$parent->stack;

        return $this;
    }

    private function compute(string $expression): float|string
    {
        try {
            $this->tokens = $this->tokenize($expression);
            $this->pos = 0;
            $result = $this->expression();
            if ($this->pos < count($this->tokens)) {
                throw new RuntimeException('#ERR!');
            }
            if (is_string($result)) {
                return $result;
            }

            return is_nan($result) || is_infinite($result) ? '#DIV/0!' : round($result, 10);
        } catch (RuntimeException $e) {
            return str_starts_with($e->getMessage(), '#') ? $e->getMessage() : '#ERR!';
        }
    }

    private function tokenize(string $s): array
    {
        preg_match_all('/\s*(\$?[A-Za-z]+\$?\d+|[A-Za-z][A-Za-z.]*|\d+(?:[.,]\d+)?(?:[eE][+-]?\d+)?|[-+*\/():;,%^])/A', $s, $m);
        if (implode('', array_map('trim', $m[0])) !== preg_replace('/\s+/', '', $s)) {
            throw new RuntimeException('#ERR!');
        }

        return $m[1];
    }

    private function peek(): ?string
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function expression(): float|string
    {
        $left = $this->term();
        while (in_array($this->peek(), ['+', '-'], true)) {
            $op = $this->tokens[$this->pos++];
            $right = $this->term();
            $left = $this->arith($left, $right, $op);
        }

        return $left;
    }

    private function term(): float|string
    {
        $left = $this->factor();
        while (in_array($this->peek(), ['*', '/'], true)) {
            $op = $this->tokens[$this->pos++];
            $right = $this->factor();
            $left = $this->arith($left, $right, $op);
        }

        return $left;
    }

    private function factor(): float|string
    {
        $token = $this->peek();
        if ($token === null) {
            throw new RuntimeException('#ERR!');
        }
        if ($token === '-' || $token === '+') {
            $this->pos++;
            $value = $this->factor();

            return $this->arith(0.0, $value, $token);
        }
        if ($token === '(') {
            $this->pos++;
            $value = $this->expression();
            $this->expect(')');

            return $this->postfix($value);
        }
        if (preg_match('/^\d/', $token)) {
            $this->pos++;

            return $this->postfix((float) str_replace(',', '.', $token));
        }
        if (preg_match('/^\$?[A-Za-z]+\$?\d+$/', $token) && ($this->tokens[$this->pos + 1] ?? null) !== '(') {
            $this->pos++;
            $value = $this->value($token);
            if ($value === '') {
                return 0.0;
            }

            return $this->postfix($value);
        }
        $name = strtoupper($token);
        if (isset(self::FUNCTIONS[$name]) && ($this->tokens[$this->pos + 1] ?? null) === '(') {
            $this->pos += 2;

            return $this->postfix($this->call(self::FUNCTIONS[$name]));
        }

        throw new RuntimeException('#ERR!');
    }

    /** Pourcentage suffixé : 20% → 0,2. */
    private function postfix(float|string $value): float|string
    {
        if ($this->peek() === '%') {
            $this->pos++;

            return is_float($value) ? $value / 100 : '#VALUE!';
        }

        return $value;
    }

    private function call(string $function): float|string
    {
        $numbers = [];
        if ($this->peek() !== ')') {
            do {
                if ($this->peek() === ',' || $this->peek() === ';') {
                    $this->pos++;
                }
                $token = $this->peek();
                if ($token !== null && preg_match('/^\$?[A-Za-z]+\$?\d+$/', $token) && ($this->tokens[$this->pos + 1] ?? null) === ':') {
                    $end = $this->tokens[$this->pos + 2] ?? throw new RuntimeException('#ERR!');
                    $this->pos += 3;
                    foreach ($this->range($token, $end) as $ref) {
                        $value = $this->value($ref);
                        if (is_string($value) && str_starts_with($value, '#')) {
                            return $value;
                        }
                        if (is_float($value)) {
                            $numbers[] = $value;
                        }
                    }
                } else {
                    $value = $this->expression();
                    if (is_string($value) && str_starts_with($value, '#')) {
                        return $value;
                    }
                    if (is_float($value)) {
                        $numbers[] = $value;
                    }
                }
            } while ($this->peek() === ',' || $this->peek() === ';');
        }
        $this->expect(')');

        return match ($function) {
            'SUM' => (float) array_sum($numbers),
            'AVERAGE' => $numbers ? array_sum($numbers) / count($numbers) : '#DIV/0!',
            'MIN' => $numbers ? (float) min($numbers) : 0.0,
            'MAX' => $numbers ? (float) max($numbers) : 0.0,
            'COUNT' => (float) count($numbers),
        };
    }

    private function range(string $from, string $to): array
    {
        [$c1, $r1] = self::parseRef($from);
        [$c2, $r2] = self::parseRef($to);
        $refs = [];
        for ($r = min($r1, $r2); $r <= max($r1, $r2); $r++) {
            for ($c = min($c1, $c2); $c <= max($c1, $c2); $c++) {
                $refs[] = self::ref($c, $r);
            }
        }

        return $refs;
    }

    private function arith(float|string $a, float|string $b, string $op): float|string
    {
        foreach ([$a, $b] as $v) {
            if (is_string($v)) {
                return str_starts_with($v, '#') ? $v : '#VALUE!';
            }
        }
        if ($op === '/' && $b == 0.0) {
            return '#DIV/0!';
        }

        return match ($op) {
            '+' => $a + $b, '-' => $a - $b, '*' => $a * $b, '/' => $a / $b,
        };
    }

    private function expect(string $token): void
    {
        if ($this->peek() !== $token) {
            throw new RuntimeException('#ERR!');
        }
        $this->pos++;
    }

    /** "1 250,50" ou "1250.5" → 1250.5 ; null si la saisie n'est pas un nombre. */
    public static function toNumber(string $raw): ?float
    {
        $clean = str_replace([' ', "\u{00A0}", "\u{202F}"], '', trim($raw));
        if (preg_match('/^[-+]?\d+(?:[.,]\d+)?(?:[eE][+-]?\d+)?$/', $clean)) {
            return (float) str_replace(',', '.', $clean);
        }

        return null;
    }
}

<?php

namespace App\Services\Sales;

use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Models\Quote;
use App\Models\User;
use App\Services\Support\NumberGenerator;
use App\Services\Support\TotalsCalculator;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function __construct(private NumberGenerator $numbers, private TotalsCalculator $calculator) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $items
     */
    public function create(array $data, array $items, User $user): Quote
    {
        $type = QuoteType::from($data['type'] ?? 'quote');

        return DB::transaction(function () use ($data, $items, $user, $type): Quote {
            $quote = Quote::query()->create([
                'number' => $this->numbers->next($type === QuoteType::Proforma ? NumberGenerator::Proforma : NumberGenerator::Quote),
                'type' => $type,
                'client_id' => $data['client_id'],
                'site_id' => $data['site_id'],
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'sales_rep_id' => $user->id,
                'status' => QuoteStatus::Draft,
                'valid_until' => $data['valid_until'] ?? now()->addDays(30),
                'notes' => $data['notes'] ?? null,
            ] + $this->calculator->document($items));

            foreach ($items as $item) {
                $quote->items()->create($item + ['total' => $this->calculator->line($item)['total']]);
            }

            return $quote;
        });
    }
}

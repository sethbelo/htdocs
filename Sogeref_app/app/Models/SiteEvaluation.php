<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteEvaluation extends Model
{
    public const CRITERIA = ['qualite' => 'Qualité des travaux', 'delais' => 'Respect des délais', 'securite' => 'Sécurité', 'budget' => 'Maîtrise du budget', 'proprete' => 'Propreté & organisation'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['evaluated_at' => 'date', 'scores' => 'array', 'score' => 'decimal:2'];
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

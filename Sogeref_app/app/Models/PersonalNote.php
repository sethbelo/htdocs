<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Policies\PersonalItemPolicy;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Note personnelle (éditeur riche façon Notion) — HTML assaini à l'écriture.
 */
#[UsePolicy(PersonalItemPolicy::class)]
class PersonalNote extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean'];
    }

    protected function content(): Attribute
    {
        return Attribute::set(fn (?string $value) => HtmlSanitizer::clean($value));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Éléments de l'utilisateur connecté dans la société courante. */
    public function scopeMine(Builder $query): Builder
    {
        return $query->where('company_id', company_id())->where('user_id', auth()->id());
    }

    /** Extrait texte brut pour les listes. */
    public function excerpt(int $length = 110): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['</p>', '</li>', '</h1>', '</h2>', '</h3>', '<br>'], ' ', (string) $this->content)), ENT_QUOTES | ENT_HTML5)));

        return str($text)->limit($length)->toString();
    }
}

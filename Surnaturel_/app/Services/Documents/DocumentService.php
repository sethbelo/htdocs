<?php

namespace App\Services\Documents;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * GED : stockage privé, versioning, liens de téléchargement signés et temporaires.
 */
class DocumentService
{
    public function store(UploadedFile $file, DocumentCategory $category, User $user, ?string $title = null, ?Model $documentable = null, bool $sensitive = false, ?int $siteId = null): Document
    {
        return DB::transaction(function () use ($file, $category, $user, $title, $documentable, $sensitive, $siteId): Document {
            $document = Document::query()->create([
                'title' => $title ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'category' => $category,
                'documentable_type' => $documentable?->getMorphClass(),
                'documentable_id' => $documentable?->getKey(),
                'site_id' => $siteId ?? $user->default_site_id,
                'current_version' => 0,
                'is_sensitive' => $sensitive,
                'uploaded_by' => $user->id,
            ]);

            $this->addVersion($document, $file, $user);

            return $document->refresh();
        });
    }

    public function addVersion(Document $document, UploadedFile $file, User $user, ?string $notes = null): DocumentVersion
    {
        $version = $document->current_version + 1;

        $documentVersion = $document->versions()->create([
            'version' => $version,
            'disk' => 'local',
            'path' => $file->store("documents/{$document->category->value}/{$document->id}", 'local'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'notes' => $notes,
            'uploaded_by' => $user->id,
        ]);

        $document->update(['current_version' => $version]);

        return $documentVersion;
    }

    public function temporaryUrl(DocumentVersion $version, int $minutes = 15): string
    {
        return URL::temporarySignedRoute('documents.download', now()->addMinutes($minutes), ['version' => $version->id]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DesignVersion;
use App\Models\DocumentVersion;
use App\Models\OrderFile;
use App\Models\Proof;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Accès aux fichiers privés (disque « local », jamais exposé publiquement).
 */
class FileController extends Controller
{
    public function orderFile(OrderFile $orderFile): StreamedResponse
    {
        Gate::authorize('view', $orderFile->order);

        return Storage::disk($orderFile->disk)->download($orderFile->path, $orderFile->original_name);
    }

    public function designVersion(DesignVersion $designVersion): StreamedResponse
    {
        Gate::authorize('view', $designVersion->designJob->order);

        return Storage::disk($designVersion->disk)->response($designVersion->path, $designVersion->original_name);
    }

    public function lockedProof(Proof $proof): StreamedResponse
    {
        Gate::authorize('view', $proof->order);
        abort_unless($proof->is_locked && $proof->locked_path && Storage::disk('local')->exists($proof->locked_path), 404);

        return Storage::disk('local')->download($proof->locked_path, basename($proof->locked_path));
    }

    /**
     * Téléchargement GED via lien signé et temporaire, doublé d'un contrôle d'autorisation.
     */
    public function document(DocumentVersion $version): StreamedResponse
    {
        Gate::authorize('view', $version->document);

        activity('Document')->performedOn($version->document)->causedBy(auth()->user())->log("Téléchargement v{$version->version}");

        return Storage::disk($version->disk)->download($version->path, $version->original_name);
    }
}

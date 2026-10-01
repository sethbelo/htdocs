<?php

namespace App\Livewire\Communication;

use App\Livewire\Communication\Concerns\CommunicationUi;
use App\Livewire\Concerns\EncryptsUrlIds;
use App\Models\CommConversation;
use App\Services\Communication\WhatsAppNumberPool;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Boîte de réception générique (WhatsApp / SMS) façon WhatsApp Web :
 * liste des conversations filtrable, fil de messages et composeur.
 * Les écrans concrets fixent le canal (WhatsAppInbox, SmsInbox).
 */
abstract class Inbox extends Component
{
    use CommunicationUi, EncryptsUrlIds;

    public string $channel = 'whatsapp';

    public ?int $selectedId = null;

    #[Url(as: 'conversation', except: null)]
    public ?string $conversationToken = null;

    public string $search = '';

    public string $status = 'open';

    public string $assignee = 'all';

    public string $number = '';

    public string $body = '';

    public bool $showNew = false;

    public string $contactSearch = '';

    public ?int $newContactId = null;

    public string $newAddress = '';

    public string $newBody = '';

    protected function encryptedUrlIds(): array
    {
        return ['selectedId' => ['conversationToken', CommConversation::class]];
    }

    public function mount(): void
    {
        $this->decodeUrlIds();
        if ($this->selectedId && ! $this->baseQuery()->whereKey($this->selectedId)->exists()) {
            $this->selectedId = null;
        }
        if ($this->selectedId) {
            $this->select($this->selectedId);
        }
    }

    protected function baseQuery(): Builder
    {
        return CommConversation::query()->where('company_id', company_id())->where('channel', $this->channel);
    }

    protected function selected(): CommConversation
    {
        return $this->baseQuery()->findOrFail($this->selectedId);
    }

    public function select(int $id): void
    {
        $conversation = $this->baseQuery()->findOrFail($id);
        $this->selectedId = $conversation->id;
        $this->body = '';
        if ($conversation->unread_count) {
            $conversation->markRead();
        }
        $this->dispatch('comm-thread-opened');
    }

    public function back(): void
    {
        $this->selectedId = null;
    }

    public function send(): void
    {
        $this->authorizeSend();
        $this->validate(['body' => ['required', 'string', 'max:'.($this->channel === 'sms' ? 1600 : 4096)]], [], ['body' => 'message']);
        $conversation = $this->selected();

        $sent = $this->attemptSend(fn () => $this->comm()->reply($conversation, trim($this->body), null, auth()->user()), 'Message envoyé.');
        if ($sent) {
            $this->body = '';
            $this->dispatch('comm-message-sent');
        }
    }

    /** Insère un modèle rendu pour le contact de la conversation ouverte. */
    public function useTemplate(int $id, string $target = 'body'): void
    {
        $template = $this->findTemplate($id);
        $contact = $target === 'newBody' ? $this->findContact($this->newContactId) : ($this->selectedId ? $this->selected()->contact : null);
        $this->{$target} = $this->comm()->renderTemplate($template->body, $contact);
    }

    public function close(): void
    {
        $this->authorizeSend();
        $this->selected()->update(['status' => 'closed']);
        $this->toast('Conversation clôturée.');
    }

    public function reopen(): void
    {
        $this->authorizeSend();
        $this->selected()->update(['status' => 'open']);
        $this->toast('Conversation rouverte.');
    }

    public function assignToMe(): void
    {
        $this->authorizeSend();
        $this->selected()->update(['assigned_to' => auth()->id()]);
        $this->toast('Conversation attribuée à vous.');
    }

    public function unassign(): void
    {
        $this->authorizeSend();
        $this->selected()->update(['assigned_to' => null]);
    }

    public function openNew(): void
    {
        $this->authorizeSend();
        $this->reset(['contactSearch', 'newContactId', 'newAddress', 'newBody']);
        $this->resetValidation();
        $this->showNew = true;
    }

    public function pickContact(int $id): void
    {
        $contact = $this->findContact($id);
        $this->newContactId = $contact?->id;
        $this->newAddress = (string) ($contact?->phone ?? '');
        $this->contactSearch = '';
    }

    public function clearContact(): void
    {
        $this->reset(['newContactId', 'newAddress']);
    }

    public function startConversation(): void
    {
        $this->authorizeSend();
        $this->validate([
            'newAddress' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[+0-9 ().-]+$/'],
            'newBody' => ['nullable', 'string', 'max:4096'],
        ], [], ['newAddress' => 'numéro', 'newBody' => 'message']);

        $contact = $this->findContact($this->newContactId);
        $address = $this->comm()->normalizePhone($this->newAddress);

        if (trim($this->newBody) !== '') {
            $to = $contact ?? $address;
            $message = $this->attemptSend(fn () => $this->channel === 'sms'
                ? $this->comm()->sendSms($to, trim($this->newBody), auth()->user())
                : $this->comm()->sendWhatsApp($to, trim($this->newBody), auth()->user()), 'Message envoyé.');
            if (! $message) {
                return;
            }
            $conversationId = $message->conversation_id;
        } else {
            $conversationId = $this->comm()->conversationFor($this->channel, $address, $contact)->id;
        }

        $this->showNew = false;
        $this->status = 'all';
        $this->select($conversationId);
    }

    public function render()
    {
        $userId = auth()->id();
        $conversations = $this->baseQuery()
            ->with(['contact', 'assignee:id,name', 'latestMessage'])
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->assignee === 'me', fn ($q) => $q->where('assigned_to', $userId))
            ->when($this->assignee === 'none', fn ($q) => $q->whereNull('assigned_to'))
            ->when($this->number !== '', fn ($q) => $q->where('sender', $this->number))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($w) => $w->where('address', 'like', $term)
                    ->orWhereHas('contact', fn ($c) => $c->where('company_name', 'like', $term)->orWhere('first_name', 'like', $term)->orWhere('last_name', 'like', $term))
                    ->orWhereHas('messages', fn ($m) => $m->where('body', 'like', $term)));
            })
            ->orderByDesc('last_message_at')->orderByDesc('id')->limit(200)->get();

        $active = $this->selectedId
            ? $this->baseQuery()->with(['contact', 'assignee:id,name', 'messages' => fn ($q) => $q->with('sender:id,name')->latest('id')->limit(150)])->find($this->selectedId)
            : null;
        $active?->setRelation('messages', $active->messages->sortBy('id')->values());

        $numbers = $this->channel === 'whatsapp' ? collect(app(WhatsAppNumberPool::class)->numbers()) : collect();

        return view('livewire.communication.inbox', [
            'conversations' => $conversations,
            'active' => $active,
            'numbers' => $numbers,
            'templates' => $this->templatesFor($this->channel),
            'unreadTotal' => (int) $this->baseQuery()->where('status', 'open')->sum('unread_count'),
            'matches' => $this->showNew ? $this->searchContacts($this->contactSearch, $this->channel) : collect(),
            'newContact' => $this->showNew ? $this->findContact($this->newContactId) : null,
            'meta' => self::channels()[$this->channel],
            'canSend' => $this->canSend(),
        ])->title($this->channel === 'sms' ? 'SMS' : 'WhatsApp');
    }
}

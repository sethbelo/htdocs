<?php

namespace App\Livewire\Chat;

use App\Livewire\Forms\CompanyContext;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Messagerie')]
class Index extends Component
{
    use WithFileUploads;

    public ?int $selectedConversation = null;

    public ?int $recipientId = null;

    public string $body = '';

    public array $attachments = [];

    public string $search = '';

    public bool $showNew = false;

    public function mount(): void
    {
        $this->selectedConversation = $this->conversationQuery()->value('conversations.id');
    }

    private function conversationQuery()
    {
        return Conversation::query()
            ->where('company_id', CompanyContext::id())
            ->whereHas('participants', fn ($query) => $query->where('users.id', auth()->id()))
            ->with(['participants:id,name,email', 'latestMessage.author:id,name'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');
    }

    private function selected(): Conversation
    {
        return $this->conversationQuery()->findOrFail($this->selectedConversation);
    }

    public function selectConversation(int $id): void
    {
        $conversation = $this->conversationQuery()->findOrFail($id);
        $this->selectedConversation = $conversation->id;
        $conversation->participants()->updateExistingPivot(auth()->id(), ['last_read_at' => now()]);
    }

    public function createConversation(): void
    {
        $this->reset('recipientId');
        $this->showNew = true;
    }

    public function startConversation(): void
    {
        $data = $this->validate(['recipientId' => ['required', 'integer', Rule::notIn([auth()->id()])]]);
        $recipient = User::where('current_company_id', CompanyContext::id())->findOrFail($data['recipientId']);

        $existing = $this->conversationQuery()
            ->where('is_group', false)
            ->whereHas('participants', fn ($query) => $query->where('users.id', $recipient->id))
            ->withCount('participants')
            ->having('participants_count', 2)
            ->first();

        if ($existing) {
            $this->selectedConversation = $existing->id;
        } else {
            $conversation = DB::transaction(function () use ($recipient) {
                $conversation = Conversation::create([
                    'company_id' => CompanyContext::id(),
                    'created_by' => auth()->id(),
                    'title' => null,
                    'is_group' => false,
                ]);
                $conversation->participants()->attach([auth()->id(), $recipient->id]);

                return $conversation;
            });
            $this->selectedConversation = $conversation->id;
        }

        $this->showNew = false;
    }

    public function send(): void
    {
        $this->validate([
            'body' => ['nullable', 'string', 'max:10000', 'required_without:attachments'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,xls,xlsx,csv,doc,docx', 'max:10240'],
        ]);

        $conversation = $this->selected();

        DB::transaction(function () use ($conversation) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => auth()->id(),
                'body' => trim($this->body) ?: null,
            ]);

            foreach ($this->attachments as $file) {
                $path = $file->store('chat/'.$conversation->id, 'local');
                $message->attachments()->create([
                    'user_id' => auth()->id(),
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                ]);
            }

            $conversation->update(['last_message_at' => now()]);
            $conversation->participants()->updateExistingPivot(auth()->id(), ['last_read_at' => now()]);
        });

        $this->reset(['body', 'attachments']);
        $this->dispatch('message-sent');
    }

    public function render()
    {
        $conversations = $this->conversationQuery()
            ->when($this->search, fn ($query) => $query->whereHas('participants', fn ($users) => $users->where('name', 'like', '%'.$this->search.'%')))
            ->get();

        $active = $this->selectedConversation
            ? $this->conversationQuery()->with(['messages' => fn ($query) => $query->with(['author:id,name', 'attachments'])->latest()->limit(100)])->find($this->selectedConversation)
            : null;

        if ($active) {
            $active->setRelation('messages', $active->messages->sortBy('created_at')->values());
        }

        $users = User::where('current_company_id', CompanyContext::id())
            ->whereKeyNot(auth()->id())->orderBy('name')->get(['id', 'name', 'email']);

        return view('livewire.chat.index', compact('conversations', 'active', 'users'));
    }
}

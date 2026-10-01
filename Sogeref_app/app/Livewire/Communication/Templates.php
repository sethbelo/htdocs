<?php

namespace App\Livewire\Communication;

use App\Livewire\Communication\Concerns\CommunicationUi;
use App\Models\CommTemplate;
use App\Models\Contact;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Modèles de messages par canal, avec variables et aperçu en direct. */
#[Title('Modèles de messages')]
class Templates extends Component
{
    use CommunicationUi;

    public string $channel = 'all';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = ['channel' => 'whatsapp', 'name' => '', 'subject' => '', 'body' => ''];

    private function guardManage(): void
    {
        abort_unless($this->canSend(), 403, 'Vous n’êtes pas autorisé à gérer les modèles.');
    }

    private function find(int $id): CommTemplate
    {
        return CommTemplate::query()->where('company_id', company_id())->findOrFail($id);
    }

    public function create(?string $channel = null): void
    {
        $this->guardManage();
        $this->resetValidation();
        $this->editingId = null;
        $this->form = ['channel' => $channel ?: ($this->channel === 'all' ? 'whatsapp' : $this->channel), 'name' => '', 'subject' => '', 'body' => ''];
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->guardManage();
        $this->resetValidation();
        $template = $this->find($id);
        $this->editingId = $template->id;
        $this->form = $template->only(['channel', 'name', 'subject', 'body']);
        $this->form['subject'] ??= '';
        $this->showForm = true;
    }

    public function insert(string $placeholder): void
    {
        $this->form['body'] = rtrim($this->form['body']).($this->form['body'] === '' ? '' : ' ').$placeholder;
    }

    public function save(): void
    {
        $this->guardManage();
        $this->validate([
            'form.channel' => 'required|in:whatsapp,sms,email',
            'form.name' => 'required|string|max:120',
            'form.subject' => ($this->form['channel'] === 'email' ? 'required' : 'nullable').'|string|max:190',
            'form.body' => 'required|string|max:'.($this->form['channel'] === 'sms' ? 1600 : 20000),
        ], [], ['form.name' => 'nom', 'form.subject' => 'objet', 'form.body' => 'message']);

        $data = ['channel' => $this->form['channel'], 'name' => trim($this->form['name']), 'subject' => $this->form['channel'] === 'email' ? trim($this->form['subject']) : null, 'body' => trim($this->form['body'])];
        $this->editingId
            ? $this->find($this->editingId)->update($data)
            : CommTemplate::create($data + ['company_id' => company_id()]);

        $this->showForm = false;
        $this->toast($this->editingId ? 'Modèle mis à jour.' : 'Modèle créé.');
    }

    public function duplicate(int $id): void
    {
        $this->guardManage();
        $copy = $this->find($id)->replicate();
        $copy->name = str($copy->name)->limit(100)->append(' (copie)')->toString();
        $copy->save();
        $this->toast('Modèle dupliqué.');
    }

    public function delete(int $id): void
    {
        $this->guardManage();
        $this->find($id)->delete();
        $this->toast('Modèle supprimé.');
    }

    public function render()
    {
        $sample = Contact::query()->where('company_id', company_id())->whereNotNull('first_name')->first()
            ?? new Contact(['first_name' => 'Marie', 'last_name' => 'Kabila', 'company_name' => 'Société Exemple']);

        return view('livewire.communication.templates', [
            'templates' => CommTemplate::query()->where('company_id', company_id())
                ->when($this->channel !== 'all', fn ($q) => $q->where('channel', $this->channel))
                ->orderBy('channel')->orderBy('name')->get(),
            'counts' => CommTemplate::query()->where('company_id', company_id())->selectRaw('channel, count(*) as n')->groupBy('channel')->pluck('n', 'channel'),
            'preview' => $this->showForm ? $this->comm()->renderTemplate((string) $this->form['body'], $sample) : '',
            'previewSubject' => $this->showForm ? $this->comm()->renderTemplate((string) ($this->form['subject'] ?? ''), $sample) : '',
            'sample' => $sample,
            'channels' => self::channels(),
            'canSend' => $this->canSend(),
        ]);
    }
}

<?php

namespace App\Livewire\Communication;

use App\Livewire\Communication\Concerns\CommunicationUi;
use App\Models\CommConversation;
use App\Models\Contact;
use Livewire\Component;

/**
 * Envoi rapide WhatsApp / SMS / e-mail depuis une fiche contact.
 * Usage : <livewire:communication.quick-send :contact="$contact" />
 * N'affiche rien si l'utilisateur n'a pas le droit d'envoyer.
 */
class QuickSend extends Component
{
    use CommunicationUi;

    public Contact $contact;

    public bool $open = false;

    public string $channel = 'whatsapp';

    public string $subject = '';

    public string $body = '';

    public function mount(Contact $contact): void
    {
        abort_unless($contact->company_id === company_id(), 404);
        $this->contact = $contact;
        $this->channel = $this->addressFor('whatsapp') ? 'whatsapp' : ($this->addressFor('email') ? 'email' : 'sms');
    }

    private function addressFor(string $channel): ?string
    {
        return $this->comm()->addressFor($this->contact, $channel);
    }

    public function start(string $channel): void
    {
        $this->authorizeSend();
        $this->channel = in_array($channel, ['whatsapp', 'sms', 'email'], true) ? $channel : 'whatsapp';
        $this->reset(['subject', 'body']);
        $this->resetValidation();
        $this->open = true;
    }

    public function useTemplate(int $id, string $target = 'body'): void
    {
        $template = $this->findTemplate($id);
        $this->body = $this->comm()->renderTemplate($template->body, $this->contact);
        if ($template->subject) {
            $this->subject = $this->comm()->renderTemplate($template->subject, $this->contact);
        }
    }

    public function send(): void
    {
        $this->authorizeSend();
        $this->validate([
            'channel' => 'required|in:whatsapp,sms,email',
            'subject' => ($this->channel === 'email' ? 'required' : 'nullable').'|string|max:190',
            'body' => 'required|string|max:'.($this->channel === 'sms' ? 1600 : 20000),
        ], [], ['subject' => 'objet', 'body' => 'message']);

        $message = $this->attemptSend(fn () => match ($this->channel) {
            'email' => $this->comm()->sendEmail($this->contact, trim($this->subject), trim($this->body), auth()->user()),
            'sms' => $this->comm()->sendSms($this->contact, trim($this->body), auth()->user()),
            default => $this->comm()->sendWhatsApp($this->contact, trim($this->body), auth()->user()),
        }, 'Message envoyé à '.$this->contact->display_name.'.');

        if ($message && ($message->status ?? null) !== 'failed') {
            $this->open = false;
        }
    }

    public function render()
    {
        $routes = ['whatsapp' => 'communication.whatsapp', 'sms' => 'communication.sms', 'email' => 'communication.emails'];

        return view('livewire.communication.quick-send', [
            'canSend' => $this->canSend(),
            'addresses' => collect(['whatsapp', 'sms', 'email'])->mapWithKeys(fn ($c) => [$c => $this->addressFor($c)])->all(),
            'channels' => self::channels(),
            'templates' => $this->open ? $this->templatesFor($this->channel) : collect(),
            'history' => CommConversation::query()->where('company_id', company_id())->where('contact_id', $this->contact->id)
                ->with('latestMessage')->latest('last_message_at')->limit(5)->get(),
            'routes' => $routes,
        ]);
    }
}

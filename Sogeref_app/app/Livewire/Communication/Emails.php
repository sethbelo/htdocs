<?php

namespace App\Livewire\Communication;

use App\Models\Contact;

/**
 * E-mails clients : fils d'échanges, envoi individuel ou groupé (un e-mail
 * distinct par destinataire), réponses dans le fil avec « RE: » automatique.
 */
class Emails extends Inbox
{
    public string $channel = 'email';

    public string $newSubject = '';

    public function openNew(): void
    {
        parent::openNew();
        $this->newSubject = '';
    }

    public function pickContact(int $id): void
    {
        $contact = $this->findContact($id);
        $this->newContactId = $contact?->id;
        $this->newAddress = (string) ($contact?->email ?? '');
        $this->contactSearch = '';
    }

    public function useTemplate(int $id, string $target = 'body'): void
    {
        parent::useTemplate($id, $target);
        $template = $this->findTemplate($id);
        if ($target === 'newBody' && $template->subject && trim($this->newSubject) === '') {
            $this->newSubject = $this->comm()->renderTemplate($template->subject, $this->findContact($this->newContactId));
        }
    }

    public function startConversation(): void
    {
        $this->authorizeSend();
        $addresses = collect(preg_split('/[,;\s]+/', $this->newAddress))->map(fn ($a) => mb_strtolower(trim($a)))->filter()->unique()->values();
        $this->newAddress = $addresses->implode(', ');
        $this->validate([
            'newAddress' => ['required', 'string', 'max:2000'],
            'newSubject' => ['required', 'string', 'max:190'],
            'newBody' => ['required', 'string', 'max:20000'],
        ], [], ['newAddress' => 'destinataire', 'newSubject' => 'objet', 'newBody' => 'message']);
        $invalid = $addresses->reject(fn ($a) => filter_var($a, FILTER_VALIDATE_EMAIL));
        if ($invalid->isNotEmpty() || $addresses->count() > 50) {
            $this->addError('newAddress', $addresses->count() > 50 ? 'Au-delà de 50 destinataires, utilisez une campagne.' : 'Adresse invalide : '.$invalid->implode(', '));

            return;
        }

        $picked = $this->findContact($this->newContactId);
        $last = null;
        $failed = 0;
        foreach ($addresses as $address) {
            $contact = $picked && mb_strtolower((string) $picked->email) === $address ? $picked : $this->comm()->findContact('email', $address);
            $to = $contact instanceof Contact && $contact->email ? $contact : $address;
            try {
                $message = $this->comm()->sendEmail($to, $this->comm()->renderTemplate(trim($this->newSubject), $contact), $this->comm()->renderTemplate(trim($this->newBody), $contact), auth()->user());
                $failed += $message->status === 'failed' ? 1 : 0;
                $last = $message;
            } catch (\DomainException $exception) {
                $failed++;
            }
        }

        if (! $last) {
            $this->toast('Aucun e-mail n’a pu être envoyé.', 'error');

            return;
        }
        $this->toast($failed ? ($addresses->count() - $failed).' e-mail(s) envoyé(s), '.$failed.' échec(s).' : ($addresses->count() > 1 ? $addresses->count().' e-mails envoyés.' : 'E-mail envoyé.'), $failed ? 'warning' : 'success');
        $this->showNew = false;
        $this->status = 'all';
        $this->select($last->conversation_id);
    }

    public function render()
    {
        return parent::render()->title('E-mails clients');
    }
}

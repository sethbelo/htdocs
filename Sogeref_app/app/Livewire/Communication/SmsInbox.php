<?php

namespace App\Livewire\Communication;

/** Boîte de réception SMS (compteur de caractères / segments). */
class SmsInbox extends Inbox
{
    public string $channel = 'sms';
}

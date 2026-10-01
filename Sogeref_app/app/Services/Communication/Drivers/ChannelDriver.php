<?php

namespace App\Services\Communication\Drivers;

use App\Services\Communication\SendResult;

/**
 * Pilote d'envoi. $message : to, from, body, subject?, html?, text?,
 * phone_number_id?, reply_to?, from_name?
 */
interface ChannelDriver
{
    public function send(array $message): SendResult;
}

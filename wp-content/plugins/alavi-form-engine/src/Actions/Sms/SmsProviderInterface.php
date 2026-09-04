<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

interface SmsProviderInterface
{
    /** @return array{success:bool,message_id?:string,error?:string,raw?:mixed} */
    public function send(SmsMessage $message): array;
}

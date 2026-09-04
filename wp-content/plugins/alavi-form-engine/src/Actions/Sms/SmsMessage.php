<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

final readonly class SmsMessage
{
    /** @param list<string> $patternValues */
    public function __construct(
        public string $recipient,
        public string $mode = 'free',
        public string $body = '',
        public string $patternCode = '',
        public array $patternValues = [],
        public string $sender = ''
    ) {}
}

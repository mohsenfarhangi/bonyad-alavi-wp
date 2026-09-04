<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Pdf;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class GeneratePdfAction implements ActionInterface
{
    public function __construct(
        private readonly PdfGenerator $generator,
        private readonly TokenResolver $tokens
    ) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        if (!$context->runtime) throw new RuntimeException('Action runtime is not available.');
        $filename = $this->tokens->resolve((string)($config['filename'] ?? ''), $context);
        $pdf = $this->generator->generate($context, $filename);
        foreach ($pdf as $key=>$value) $context->runtime->set('pdf_'.$key, $value);
    }
}

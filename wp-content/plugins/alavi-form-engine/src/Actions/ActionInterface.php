<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

interface ActionInterface
{
    public function handle(ActionContext $context, array $config = []): void;
}

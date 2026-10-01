<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\View;

interface ContextComposer
{
    public function supports(string $template): bool;

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function compose(array $context, string $template): array;
}

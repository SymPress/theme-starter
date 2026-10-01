<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\WordPress;

use SymPress\Assets\Asset;
use SymPress\Assets\Loader\EncoreEntrypointsLoader;

/** Pass only validated entries to the shared loader, without re-reading untrusted entries. */
final class AssetLoader extends EncoreEntrypointsLoader
{
    /**
     * @param array<string, array<string, list<string>>> $entries
     * @return array<Asset>
     */
    public function fromEntries(array $entries, string $file): array
    {
        return $this->parseData(['entrypoints' => $entries], $file);
    }
}

<?php

namespace App\Filament\Commands\FileGenerators\Resources\Traits;

use Nette\PhpGenerator\Literal;

trait FormatVariableToTextTrait
{
    private function transformerVariableNameInText(string $componentName): array
    {
        $label = str($componentName)
            ->beforeLast('.')
            ->afterLast('.')
            ->kebab()
            ->replace(['-', '_'], ' ')
            ->ucfirst();

        return [
            new Literal(
                <<<PHP
                __('$label')
            PHP
            ),
        ];
    }
}

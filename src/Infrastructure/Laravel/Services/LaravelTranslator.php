<?php

namespace Project\Infrastructure\Laravel\Services;

use Illuminate\Contracts\Translation\Translator;
use Project\Common\Services\Translator\TranslatorInterface;
use Project\Common\Services\Environment\EnvironmentInterface;

class LaravelTranslator implements TranslatorInterface
{
    public function __construct(
        private readonly Translator $translator,
        private readonly EnvironmentInterface $environment,
        private readonly string $namespace = 'Project',
    ) {}

    public function translate(string $key, string $default, array $params = []): string
    {
        $fullKey = "$this->namespace::$key";
        $translation = $this->translator->get($fullKey, $params, $this->environment->getLanguage()->value);
        if ($translation === $fullKey) {
            return str_replace(array_keys($params), array_values($params), $default);
        }

        return $translation;
    }
}
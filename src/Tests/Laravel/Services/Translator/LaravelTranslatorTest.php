<?php

namespace Laravel\Services\Translator;

use Project\Tests\Laravel\TestCase;
use Illuminate\Contracts\Translation\Translator;
use Project\Common\Services\Environment\Language;
use Project\Common\Services\Environment\EnvironmentInterface;
use Project\Infrastructure\Laravel\Services\LaravelTranslator;

class LaravelTranslatorTest extends TestCase
{
    private Translator $laravelTranslator;
    private EnvironmentInterface $environment;
    private Language $language;
    private string $namespace = 'Project';

    private LaravelTranslator $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->laravelTranslator = $this->getMockBuilder(Translator::class)->getMock();
        $this->environment = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $this->language = Language::default();
        $this->translator = new LaravelTranslator($this->laravelTranslator, $this->environment, $this->namespace);
    }

    public function testTranslate()
    {
        $key = uniqid();
        $default = uniqid();
        $params = ['param' => uniqid()];

        $this->environment->expects($this->once())
            ->method('getLanguage')
            ->willReturn($this->language);

        $translated = uniqid();
        $this->laravelTranslator->expects($this->once())
            ->method('get')
            ->with("$this->namespace::$key", $params, $this->language->value)
            ->willReturn($translated);

        $this->assertSame($translated, $this->translator->translate($key, $default, $params));
    }

    public function testTranslateIfTranslationDoesNotExists()
    {
        $key = uniqid();
        $default = "param equals 1";
        $params = ['param' => 1];

        $this->environment->expects($this->once())
            ->method('getLanguage')
            ->willReturn($this->language);

        $fullTranslationKey = "$this->namespace::$key";
        $this->laravelTranslator->expects($this->once())
            ->method('get')
            ->with($fullTranslationKey, $params, $this->language->value)
            ->willReturn($fullTranslationKey);

        $this->assertSame("1 equals 1", $this->translator->translate($key, $default, $params));
    }
}
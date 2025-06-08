<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface
{
    private string $defaultLocale;

    public function __construct(string $defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Try to get locale from _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->setLocale($locale);
            return;
        }

        // Try to get locale from session
        if ($request->hasSession() && $locale = $request->getSession()->get('_locale')) {
            $request->setLocale($locale);
            return;
        }

        // Try to get locale from browser
        $locale = $request->getPreferredLanguage(locales: ['en', 'uk']) ?: $this->defaultLocale;
        $request->setLocale($locale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}

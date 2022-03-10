<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Subscriber;

use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemConfigSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onSystemConfigChanged'
        ];
    }

    public function onSystemConfigChanged(SystemConfigChangedEvent $event)
    {
        $key = $event->getKey();
        $configPrefix = 'WexoEasyTranslate.config.';

        if (substr($key, 0, strlen($configPrefix)) === $configPrefix) {
            if (preg_match('/(username|clientId|clientSecret)/', $key) === 1) {
                $this->systemConfigService->set($configPrefix . 'accessToken', null);
                $this->systemConfigService->set($configPrefix . 'refreshToken', null);
                $this->systemConfigService->set($configPrefix . 'teamIdentifier', null);
            }
        }
    }
}

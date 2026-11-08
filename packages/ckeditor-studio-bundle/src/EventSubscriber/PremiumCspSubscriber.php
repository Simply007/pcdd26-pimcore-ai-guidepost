<?php
declare(strict_types=1);

/**
 * CKEditor integration bundle for Pimcore Studio.
 *
 * Conference demo (Pimcore Community Dev Days 2026), modeled architecturally
 * on pimcore/quill-bundle. MIT licensed.
 */

namespace Simply007\CKEditorStudioBundle\EventSubscriber;

use Pimcore\Bundle\StudioUiBundle\Event\Csp\CspEvent;
use Pimcore\Bundle\StudioUiBundle\Security\Csp\ContentSecurityPolicyHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Pimcore Studio sends a strict Content Security Policy whose connect-src
 * allows only 'self' and a few Pimcore hosts. The premium features talk to
 * CKEditor Cloud Services from the browser (CKEditor AI over HTTPS, real-time
 * collaboration over a WebSocket) and commercial/trial license keys report
 * usage to the CKEditor usage endpoint, so all of that would be blocked.
 *
 * This subscriber widens connect-src ONLY when a license key is configured
 * and at least one premium gate is on, so the free (GPL) path keeps Studio's
 * policy byte for byte.
 */
final class PremiumCspSubscriber implements EventSubscriberInterface
{
    /** Cloud Services hosts (US and EU regions, REST and WebSocket) and the license usage endpoint. */
    public const CONNECT_ORIGINS = [
        'https://*.cke-cs.com',
        'wss://*.cke-cs.com',
        'https://*.cke-cs-eu.com',
        'wss://*.cke-cs-eu.com',
        'https://proxy-event.ckeditor.com',
    ];

    /**
     * @param array<string, mixed> $premium
     */
    public function __construct(
        private readonly string $licenseKey,
        private readonly array $premium,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [CspEvent::class => 'onCspEvent'];
    }

    public function onCspEvent(CspEvent $event): void
    {
        if (!$this->isPremiumActive()) {
            return;
        }

        $event->getCspHandler()->addAllowedUrls(ContentSecurityPolicyHandlerInterface::CONNECT_OPT, self::CONNECT_ORIGINS);
    }

    private function isPremiumActive(): bool
    {
        if ($this->licenseKey === '') {
            return false;
        }

        return (bool) ($this->premium['ai']['enabled'] ?? false)
            || (bool) ($this->premium['realtime_collaboration']['enabled'] ?? false);
    }
}

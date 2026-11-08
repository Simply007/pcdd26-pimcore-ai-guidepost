<?php
declare(strict_types=1);

/**
 * CKEditor integration bundle for Pimcore Studio.
 *
 * Conference demo (Pimcore Community Dev Days 2026), modeled architecturally
 * on pimcore/quill-bundle. MIT licensed.
 */

namespace Simply007\CKEditorStudioBundle\AppConfig;

use Simply007\CKEditorStudioBundle\CloudServices\CloudServicesTokenGenerator;
use Simply007\CKEditorStudioBundle\Controller\CloudServicesTokenController;
use Pimcore\Bundle\StudioUiBundle\AppConfig\AppConfigProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Surfaces the bundle configuration (license key, toolbar preset, premium gates)
 * to the Studio frontend at runtime: Studio core merges every tagged provider
 * into the `data-app-config` JSON attribute on the Studio shell page, which the
 * frontend reads through `appConfig` from `@pimcore/studio-ui-bundle/app`.
 *
 * Runtime means: changing the license key or toolbar needs only a cache:clear,
 * never a frontend rebuild, and no key ever lands in the committed build output.
 *
 * Note: like every CKEditor integration, the license key ends up readable in the
 * browser. CKEditor license keys are designed for that; keep real secrets (for
 * example an AI adapter's API key) on the server side.
 */
final class StudioAppConfigProvider implements AppConfigProviderInterface
{
    /**
     * @param string[] $toolbar
     * @param array<string, mixed> $premium
     */
    public function __construct(
        private readonly string $editor,
        private readonly string $licenseKey,
        private readonly array $toolbar,
        private readonly array $premium,
        private readonly string $cloudServicesWebSocketUrl,
        private readonly CloudServicesTokenGenerator $tokenGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getAppConfig(): array
    {
        $rtc = $this->premium['realtime_collaboration'] ?? [];
        $ai = $this->premium['ai'] ?? [];

        // Explicit yaml values win; otherwise fall back to the bundle's own token
        // endpoint (when the Cloud Services environment is configured) and the
        // env-driven WebSocket URL. Resolved per request, so env changes need only
        // a cache:clear. Only the URLs reach the browser, never the access key.
        $tokenUrl = $rtc['token_url'] ?? null;
        if (($tokenUrl === null || $tokenUrl === '') && $this->tokenGenerator->isConfigured()) {
            $tokenUrl = $this->urlGenerator->generate(
                CloudServicesTokenController::ROUTE_NAME,
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        $webSocketUrl = $rtc['web_socket_url'] ?? null;
        if (($webSocketUrl === null || $webSocketUrl === '') && $this->cloudServicesWebSocketUrl !== '') {
            $webSocketUrl = $this->cloudServicesWebSocketUrl;
        }

        return [
            'ckeditorStudio' => [
                // Which editor owns Studio's wysiwyg slot; applied by the frontend selector.
                'editor' => $this->editor,
                // null = not configured = frontend falls back to licenseKey 'GPL'.
                'licenseKey' => $this->licenseKey === '' ? null : $this->licenseKey,
                'toolbar' => array_values($this->toolbar),
                'premium' => [
                    'ai' => [
                        'enabled' => (bool) ($ai['enabled'] ?? false),
                        'config' => (object) ($ai['config'] ?? []),
                        'container' => ($ai['container'] ?? 'sidebar') === 'overlay' ? 'overlay' : 'sidebar',
                    ],
                    'realtimeCollaboration' => [
                        'enabled' => (bool) ($rtc['enabled'] ?? false),
                        'tokenUrl' => $tokenUrl === '' ? null : $tokenUrl,
                        'webSocketUrl' => $webSocketUrl === '' ? null : $webSocketUrl,
                    ],
                ],
            ],
        ];
    }
}

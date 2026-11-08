<?php
declare(strict_types=1);

/**
 * CKEditor integration bundle for Pimcore Studio.
 *
 * Conference demo (Pimcore Community Dev Days 2026), modeled architecturally
 * on pimcore/quill-bundle. MIT licensed.
 */

namespace Simply007\CKEditorStudioBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * ckeditor_studio:
 *   editor: ckeditor                             # ckeditor | quill | default (single selection point)
 *   license_key: '%env(CKEDITOR_LICENSE_KEY)%'   # default; empty = free feature set with licenseKey 'GPL'
 *   toolbar: [ 'undo', 'redo', '|', ... ]        # default preset below
 *   premium:
 *     ai:
 *       enabled: false
 *       config: {}                               # raw CKEditor "ai" config (adapter etc.), passed through
 *       container: sidebar                       # sidebar (docked panel) | overlay
 *     realtime_collaboration:
 *       enabled: false
 *       token_url: null                          # null = bundle token endpoint when cloud_services is set
 *       web_socket_url: null                     # null = cloud_services.web_socket_url
 *   cloud_services:
 *     environment_id: '%env(CKEDITOR_CS_ENVIRONMENT_ID)%'   # token aud
 *     access_key: '%env(CKEDITOR_CS_ACCESS_KEY)%'           # signs tokens, server side only
 *     web_socket_url: '%env(CKEDITOR_CS_WEBSOCKET_URL)%'
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * Default toolbar preset. Every item here is backed by a plugin the frontend
     * build ships (open source feature set), so it works with and without a
     * license key.
     *
     * @var string[]
     */
    public const DEFAULT_TOOLBAR = [
        'undo', 'redo', '|',
        'heading', '|',
        'bold', 'italic', 'underline', '|',
        'alignment', '|',
        'bulletedList', 'numberedList', 'outdent', 'indent', '|',
        'blockQuote', 'link', 'insertTable', '|',
        'sourceEditing', 'fullscreen',
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ckeditor_studio');

        $treeBuilder->getRootNode()
            ->children()
                ->enumNode('editor')
                    ->info(
                        'Which editor Studio uses for wysiwyg fields: "ckeditor" (this bundle), "quill" (the editor another '
                        . 'bundle such as PimcoreQuillBundle registered; falls back to CKEditor when none did) or "default" '
                        . '(Studio\'s built-in editor). Editor bundles can stay enabled side by side.'
                    )
                    ->values(['ckeditor', 'quill', 'default'])
                    ->defaultValue('ckeditor')
                ->end()
                ->scalarNode('license_key')
                    ->info(
                        'CKEditor license key. Defaults to the CKEDITOR_LICENSE_KEY environment variable. '
                        . 'Empty or unset: the editor runs the open source feature set with licenseKey "GPL". '
                        . 'Set: the key is used as licenseKey and premium features may be enabled below.'
                    )
                    ->defaultValue('%env(CKEDITOR_LICENSE_KEY)%')
                ->end()
                ->arrayNode('toolbar')
                    ->info(
                        'CKEditor toolbar items (strings, "|" is a separator). Items must be backed by a plugin '
                        . 'the frontend build ships; unknown items are skipped by CKEditor with a console warning.'
                    )
                    ->scalarPrototype()->end()
                    ->defaultValue(self::DEFAULT_TOOLBAR)
                ->end()
                ->arrayNode('premium')
                    ->info('Premium feature gates. Loaded on demand by the frontend (dynamic import); see the bundle README before enabling.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('ai')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->info('Enable CKEditor AI. Requires license_key; see the bundle README for the AI service prerequisites.')
                                    ->defaultFalse()
                                ->end()
                                ->variableNode('config')
                                    ->info('Raw CKEditor "ai" configuration (adapter, model, endpoints). Passed to the frontend as-is.')
                                    ->defaultValue([])
                                ->end()
                                ->enumNode('container')
                                    ->info(
                                        'Where the AI UI renders: "sidebar" docks one AI panel per element next to the content '
                                        . '(pushes the content aside), "overlay" floats it over the page.'
                                    )
                                    ->values(['sidebar', 'overlay'])
                                    ->defaultValue('sidebar')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('realtime_collaboration')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->info('Enable real-time collaboration. Requires license_key and Cloud Services credentials (token_url, web_socket_url).')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('token_url')
                                    ->info(
                                        'CKEditor Cloud Services token endpoint (cloudServices.tokenUrl). Null: the bundle\'s own '
                                        . 'endpoint (/pimcore-studio/api/ckeditor-studio/cs-token) when cloud_services is configured. '
                                        . 'Also authenticates CKEditor AI.'
                                    )
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('web_socket_url')
                                    ->info('CKEditor Cloud Services WebSocket URL (cloudServices.webSocketUrl). Null: cloud_services.web_socket_url.')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cloud_services')
                    ->info(
                        'CKEditor Cloud Services environment for the bundle\'s token endpoint. All values default to environment '
                        . 'variables; leave them empty to disable the endpoint. The access key never leaves the server.'
                    )
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('environment_id')
                            ->info('Environment id (token "aud" claim).')
                            ->defaultValue('%env(CKEDITOR_CS_ENVIRONMENT_ID)%')
                        ->end()
                        ->scalarNode('access_key')
                            ->info('Environment access key, signs the HS256 tokens. Secret: keep it in .env.local or a vault.')
                            ->defaultValue('%env(CKEDITOR_CS_ACCESS_KEY)%')
                        ->end()
                        ->scalarNode('web_socket_url')
                            ->info('Environment WebSocket URL, used when premium.realtime_collaboration.web_socket_url is null.')
                            ->defaultValue('%env(CKEDITOR_CS_WEBSOCKET_URL)%')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

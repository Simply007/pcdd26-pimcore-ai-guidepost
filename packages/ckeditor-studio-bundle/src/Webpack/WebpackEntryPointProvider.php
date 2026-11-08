<?php
declare(strict_types=1);

/**
 * CKEditor integration bundle for Pimcore Studio.
 *
 * Conference demo (Pimcore Community Dev Days 2026), modeled architecturally
 * on pimcore/quill-bundle. MIT licensed.
 */

namespace Simply007\CKEditorStudioBundle\Webpack;

use Pimcore\Bundle\StudioUiBundle\Webpack\WebpackEntryPointProviderInterface;

/**
 * Registers this bundle's Studio frontend build with Studio core.
 *
 * Unlike pimcore/quill-bundle, which ships its frontend as a committed zip archive
 * (BuildArchiveProviderInterface + BuildArchiveExtractionTrait), this bundle is a
 * local path repository: the rsbuild output in public/studio/build/<build-id>/ is
 * used directly, so the plain entry point provider interface with a glob is enough.
 */
final class WebpackEntryPointProvider implements WebpackEntryPointProviderInterface
{
    /**
     * @return string[]
     */
    public function getEntryPointsJsonLocations(): array
    {
        return glob(__DIR__ . '/../../public/studio/build/*/entrypoints.json') ?: [];
    }

    /**
     * @return string[]
     */
    public function getEntryPoints(): array
    {
        return ['exposeRemote'];
    }

    /**
     * @return string[]
     */
    public function getOptionalEntryPoints(): array
    {
        return [];
    }
}

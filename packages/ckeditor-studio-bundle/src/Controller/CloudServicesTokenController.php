<?php
declare(strict_types=1);

/**
 * CKEditor integration bundle for Pimcore Studio.
 *
 * Conference demo (Pimcore Community Dev Days 2026), modeled architecturally
 * on pimcore/quill-bundle. MIT licensed.
 */

namespace Simply007\CKEditorStudioBundle\Controller;

use Simply007\CKEditorStudioBundle\CloudServices\CloudServicesTokenGenerator;
use Pimcore\Security\User\User as PimcoreSecurityUser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CKEditor Cloud Services token endpoint (config.cloudServices.tokenUrl).
 *
 * Lives under /pimcore-studio/api, so the Studio firewall and its
 * `ROLE_PIMCORE_USER` access rule apply: only users logged into Studio get a
 * token, and the token's `sub` is their Pimcore user id. The editor fetches it
 * with the Studio session cookie (same origin).
 */
#[AsController]
final class CloudServicesTokenController
{
    public const ROUTE_NAME = 'ckeditor_studio_cs_token';

    public function __construct(
        private readonly CloudServicesTokenGenerator $tokenGenerator,
    ) {
    }

    #[Route('/pimcore-studio/api/ckeditor-studio/cs-token', name: self::ROUTE_NAME, methods: ['GET'])]
    #[IsGranted('ROLE_PIMCORE_USER')]
    public function __invoke(#[CurrentUser] ?PimcoreSecurityUser $user): Response
    {
        if ($user === null) {
            return new Response('Not authenticated', Response::HTTP_UNAUTHORIZED, ['Content-Type' => 'text/plain']);
        }

        if (!$this->tokenGenerator->isConfigured()) {
            return new Response(
                'CKEditor Cloud Services token endpoint is not configured (CKEDITOR_CS_ENVIRONMENT_ID, CKEDITOR_CS_ACCESS_KEY).',
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['Content-Type' => 'text/plain']
            );
        }

        $model = $user->getUser();
        $fullName = trim(($model->getFirstname() ?? '') . ' ' . ($model->getLastname() ?? ''));

        $token = $this->tokenGenerator->generate(
            (string) $user->getId(),
            $fullName !== '' ? $fullName : $user->getUserIdentifier(),
            $model->getEmail()
        );

        return new Response($token, Response::HTTP_OK, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}

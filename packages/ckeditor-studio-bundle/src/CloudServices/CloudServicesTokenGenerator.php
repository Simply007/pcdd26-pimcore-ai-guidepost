<?php
declare(strict_types=1);

/**
 * CKEditor integration bundle for Pimcore Studio.
 *
 * Conference demo (Pimcore Community Dev Days 2026), modeled architecturally
 * on pimcore/quill-bundle. MIT licensed.
 */

namespace Simply007\CKEditorStudioBundle\CloudServices;

/**
 * Issues CKEditor Cloud Services tokens: HS256 JWTs signed with the
 * environment's access key, with the environment id as `aud`.
 *
 * One token covers both premium gates: `auth.collaboration` (real-time
 * collaboration) and `auth.ai.permissions` (CKEditor AI). Claim shape per the
 * Cloud Services token endpoint and CKEditor AI authentication guides:
 * https://ckeditor.com/docs/cs/latest/developer-resources/security/token-endpoint.html
 * https://ckeditor.com/docs/cs/latest/guides/ckeditor-ai/authentication.html
 *
 * Zero dependencies on purpose (hash_hmac + base64url): the bundle stays
 * installable without pulling a JWT library into the host project.
 */
final class CloudServicesTokenGenerator
{
    /**
     * Demo scope: every authenticated Studio user may write in every channel.
     * Narrow this per channel (`pimcore-<type>-<id>-<field>`) for production.
     */
    private const COLLABORATION = ['*' => ['role' => 'writer']];

    /**
     * The "end user" profile from the CKEditor AI authentication guide: chat,
     * actions and reviews on the recommended agent model. Scopes are not
     * validated when signing; a misspelled one simply grants nothing.
     */
    private const AI_PERMISSIONS = [
        'ai:conversations:read',
        'ai:conversations:write',
        'ai:actions:*',
        'ai:reviews:*',
        'ai:models:agent',
    ];

    /** Cloud Services accepts tokens up to 24 hours old; clients refresh on demand. */
    private const TTL_SECONDS = 3600;

    public function __construct(
        private readonly string $environmentId,
        private readonly string $accessKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->environmentId !== '' && $this->accessKey !== '';
    }

    public function generate(string $userId, string $userName, ?string $userEmail = null, ?int $now = null): string
    {
        if (!$this->isConfigured()) {
            throw new \LogicException('CKEditor Cloud Services token generator is not configured.');
        }

        $now ??= time();

        $user = ['name' => $userName];
        if ($userEmail !== null && $userEmail !== '') {
            $user['email'] = $userEmail;
        }

        $header = self::encodeJson(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = self::encodeJson([
            'aud' => $this->environmentId,
            'iat' => $now,
            'exp' => $now + self::TTL_SECONDS,
            'sub' => $userId,
            'user' => $user,
            'auth' => [
                'collaboration' => self::COLLABORATION,
                'ai' => ['permissions' => self::AI_PERMISSIONS],
            ],
        ]);

        $signature = hash_hmac('sha256', $header . '.' . $payload, $this->accessKey, true);

        return $header . '.' . $payload . '.' . self::base64url($signature);
    }

    private static function encodeJson(array $value): string
    {
        return self::base64url(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private static function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}

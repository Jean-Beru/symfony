<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\AssetMapper\ImportMap\Sri;

use Symfony\Component\AssetMapper\Exception\RuntimeException;
use Symfony\Component\Mime\Exception\InvalidArgumentException;

class OpenSslRsiHashGenerator implements SriHashGeneratorInterface
{
    /** @var resource|\OpenSSLAsymmetricKey */
    private mixed $pkrId;

    /**
     * @param string $privateKey The private key as a string or the path to the file containing the private key, should be prefixed with file:// (in PEM format)
     * @param ?string $passphrase A passphrase of the private key (if any)
     */
    public function __construct(
        #[\SensitiveParameter] public readonly string $privateKey,
        #[\SensitiveParameter] public readonly ?string $privateKeyPassphrase = null,
    ) {
        if (!\extension_loaded('openssl')) {
            throw new \LogicException(sprintf('PHP extension "openssl" is required to use %s.', self::class));
        }

        $this->pkrId = openssl_pkey_get_private($privateKey, $privateKeyPassphrase) ?: throw new InvalidArgumentException('Unable to load private key: '.openssl_error_string());
    }

    public function generate(string $data): string
    {
        openssl_sign($data, $signature, $this->pkrId, OPENSSL_ALGO_SHA384) ?: throw new RuntimeException(sprintf('Failed to sign data. Error: "%s".', openssl_error_string()));

        return $signature;
    }
}

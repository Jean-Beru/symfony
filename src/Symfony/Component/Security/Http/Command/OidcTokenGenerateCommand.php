<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Http\AccessToken\Oidc\OidcTokenGenerator;

/**
 * Generates an OIDC token.
 *
 * @final
 */
#[AsCommand(name: 'security:oidc:generate', description: 'Generate an OIDC token')]
final class OidcTokenGenerateCommand extends Command
{
    /** @var array<string, OidcTokenGenerator> */
    private array $generators = [];

    protected function configure(): void
    {
        $this
            ->addArgument('user-identifier', InputArgument::REQUIRED, 'User identifier')
            ->addOption('firewall', null, InputOption::VALUE_REQUIRED, 'Firewall')
            ->addOption('algorithm', null, InputOption::VALUE_REQUIRED, 'Algorithm name to use to sign')
            ->addOption('issuer', null, InputOption::VALUE_REQUIRED, 'Set the Issuer claim (iss)')
            ->addOption('ttl', null, InputOption::VALUE_REQUIRED, 'Set the Expiration Time claim (exp) (time to live in seconds)')
            ->addOption('not-before', null, InputOption::VALUE_REQUIRED, 'Set the Not Before claim (nbf)')
        ;
    }

    public function addGenerator(string $firewall, OidcTokenGenerator $oidcTokenGenerator): void
    {
        $this->generators[$firewall] = $oidcTokenGenerator;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $generator = $this->getGenerator($input->getOption('firewall'));
        $token = $generator->generate(
            $input->getArgument('user-identifier'),
            $input->getOption('algorithm'),
            $input->getOption('issuer'),
            $input->getOption('ttl'),
            $input->getOption('not-before'),
        );

        $output->writeln($token);

        return self::SUCCESS;
    }

    private function getGenerator(?string $firewall): OidcTokenGenerator
    {
        if (0 === count($this->generators)) {
            throw new \InvalidArgumentException('No OIDC token generator configured.');
        }

        if ($firewall) {
            return $this->generators[$firewall] ?? throw new \InvalidArgumentException(sprintf('Invalid firewall. Available firewalls: %s', implode(', ', array_keys($this->generators))));
        }

        if (1 === count($this->generators)) {
            return end($this->generators);
        }

        throw new \InvalidArgumentException(sprintf('Please choose an firewall. Available firewalls: %s', implode(', ', array_keys($this->generators))));
    }
}

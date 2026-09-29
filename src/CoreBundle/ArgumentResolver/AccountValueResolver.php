<?php

namespace Runalyze\Bundle\CoreBundle\ArgumentResolver;

use Generator;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * TEMPORARY DEBUG INSTRUMENTATION: writes to error_log() at every decision point in supports(),
 * so the actual runtime values (argument type match, token presence, token's getUser() result)
 * can be observed directly for the /dashboard "Cannot autowire argument $account" failure -
 * rather than guessing further at what TokenStorageInterface::getToken() actually returns at this
 * point in the request lifecycle under the new authenticator system. Remove all error_log() calls
 * once the actual cause is identified from the resulting log output.
 */
class AccountValueResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        error_log(sprintf(
            '[AccountValueResolver::supports] argument name=%s type=%s',
            $argument->getName(),
            $argument->getType() ?? 'NULL'
        ));

        if ($argument->getType() !== Account::class) {
            error_log('[AccountValueResolver::supports] -> false (type mismatch)');

            return false;
        }

        $token = $this->tokenStorage->getToken();

        error_log(sprintf(
            '[AccountValueResolver::supports] token=%s',
            $token instanceof TokenInterface ? get_class($token) : 'NULL'
        ));

        if (! $token instanceof TokenInterface) {
            error_log('[AccountValueResolver::supports] -> false (no token)');

            return false;
        }

        $user = $token->getUser();

        error_log(sprintf(
            '[AccountValueResolver::supports] token->getUser()=%s',
            is_object($user) ? get_class($user) : var_export($user, true)
        ));

        $result = $user instanceof Account;

        error_log(sprintf('[AccountValueResolver::supports] -> %s', $result ? 'true' : 'false (user is not an Account instance)'));

        return $result;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        error_log(sprintf(
            '[AccountValueResolver::resolve] user=%s',
            is_object($user) ? get_class($user) : var_export($user, true)
        ));

        if (! $user instanceof Account) {
            return;
        }

        yield $user;
    }
}

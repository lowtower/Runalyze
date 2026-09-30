<?php

namespace Runalyze\Bundle\CoreBundle\ArgumentResolver;

use Generator;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AccountValueResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->getType() !== Account::class) {
            return false;
        }

        $token = $this->tokenStorage->getToken();

        if (! $token instanceof TokenInterface) {
            return false;
        }

        return $token->getUser() instanceof Account;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        yield $this->tokenStorage->getToken()
            ->getUser();
    }
}

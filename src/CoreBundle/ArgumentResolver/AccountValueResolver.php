<?php

namespace Runalyze\Bundle\CoreBundle\ArgumentResolver;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AccountValueResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
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

    /**
     * @return \Generator
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->tokenStorage->getToken()
            ->getUser();
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\ArgumentResolver;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AccountValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * @return Account[] the logged in account, or an empty list if there is none
     */
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        if ($argument->getType() !== Account::class) {
            return [];
        }

        $token = $this->tokenStorage->getToken();

        if (! $token instanceof TokenInterface) {
            return [];
        }

        $user = $token->getUser();

        if (! $user instanceof Account) {
            return [];
        }

        return [$user];
    }
}

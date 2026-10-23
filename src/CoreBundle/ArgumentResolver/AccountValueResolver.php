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
    ) {}

    /**
     * @return array<int, Account|null> the logged in account, null for a nullable argument without login, otherwise an empty list
     */
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        if ($argument->getType() !== Account::class) {
            return [];
        }

        $token = $this->tokenStorage->getToken();
        $user = $token instanceof TokenInterface ? $token->getUser() : null;

        if ($user instanceof Account) {
            return [$user];
        }

        return $argument->isNullable() ? [null] : [];
    }
}

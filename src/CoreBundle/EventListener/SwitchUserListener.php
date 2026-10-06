<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

/**
 * SwitchUserListener allows a user to impersonate another one temporarily
 * (like the Unix su command).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SwitchUserListener
{
    private readonly string $providerKey;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserProviderInterface $provider,
        private readonly UserCheckerInterface $userChecker,
        string $providerKey,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly ?LoggerInterface $logger = null,
        private readonly string $usernameParameter = '_switch_user',
        private readonly string $role = 'ROLE_ADMIN',
        private readonly ?EventDispatcherInterface $dispatcher = null
    ) {
        if (empty($providerKey)) {
            throw new InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->providerKey = $providerKey;
    }

    /**
     * Handles the switch to another user.
     *
     * @throws LogicException if switching to a user failed
     */
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (! $request->get($this->usernameParameter)) {
            return;
        }

        try {
            if ($request->get($this->usernameParameter) === '_exit') {
                $this->tokenStorage->setToken($this->attemptExitUser());
            } else {
                $this->tokenStorage->setToken($this->attemptSwitchUser($request));
            }
        } catch (AuthenticationException $authenticationException) {
            throw new LogicException(sprintf('Switch User failed: "%s"', $authenticationException->getMessage()), $authenticationException->getCode(), $authenticationException);
        }

        $request->query->remove($this->usernameParameter);
        $request->server->set('QUERY_STRING', http_build_query($request->query->all()));

        $response = new RedirectResponse($request->getUri(), Response::HTTP_FOUND);

        $event->setResponse($response);
    }

    /**
     * Attempts to switch to another user.
     *
     * @throws LogicException
     * @throws AccessDeniedException
     */
    private function attemptSwitchUser(Request $request): ?TokenInterface
    {
        $token = $this->tokenStorage->getToken();
        $originalToken = $this->getOriginalToken($token);

        if ($originalToken !== false) {
            if ($token->getUserIdentifier() === $request->get($this->usernameParameter)) {
                return $token;
            }

            throw new LogicException(sprintf('You are already switched to "%s" user.', $token->getUserIdentifier()));
        }

        if ($this->accessDecisionManager->decide($token, [$this->role]) === false) {
            throw new AccessDeniedException();
        }

        $username = $request->get($this->usernameParameter);

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info('Attempting to switch to user.', [
                'username' => $username,
            ]);
        }

        $user = $this->provider->loadUserByIdentifier($username);
        if ($user instanceof Account && $user->getAllowSupport() === false) {
            throw new AccessDeniedException('User does not allow access to his account (allow_support)');
        }

        $this->userChecker->checkPostAuth($user);

        $roles = $user->getRoles();
        $roles[] = 'ROLE_PREVIOUS_ADMIN';

        $newToken = new SwitchUserToken(
            $user,
            $this->providerKey,
            $roles,
            $this->tokenStorage->getToken()
        );

        if ($this->dispatcher instanceof EventDispatcherInterface) {
            $switchEvent = new SwitchUserEvent($request, $newToken->getUser());
            $this->dispatcher->dispatch($switchEvent);
        }

        return $newToken;
    }

    /**
     * Attempts to exit from an already switched user.
     *
     * @throws AuthenticationCredentialsNotFoundException
     */
    private function attemptExitUser(): TokenInterface
    {
        if (false === $original = $this->getOriginalToken($this->tokenStorage->getToken())) {
            throw new AuthenticationCredentialsNotFoundException('Switch User failed: No original token found.');
        }

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info('Switch user exited for user.', [
                'username' => $original->getUserIdentifier(),
            ]);
        }

        return $original;
    }

    /**
     * Gets the original Token from a switched one.
     *
     * @return TokenInterface|false The original TokenInterface instance, false if the current TokenInterface is not switched
     */
    private function getOriginalToken(?TokenInterface $token): TokenInterface|false
    {
        if (! $token instanceof SwitchUserToken) {
            return false;
        }

        return $token->getOriginalToken();
    }
}

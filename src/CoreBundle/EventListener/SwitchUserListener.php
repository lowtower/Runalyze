<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
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
        } catch (AuthenticationException $e) {
            throw new LogicException(sprintf('Switch User failed: "%s"', $e->getMessage()), $e->getCode(), $e);
        }

        $request->query->remove($this->usernameParameter);
        $request->server->set('QUERY_STRING', http_build_query($request->query->all()));

        $response = new RedirectResponse($request->getUri(), 302);

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
            if ($token->getUsername() === $request->get($this->usernameParameter)) {
                return $token;
            }

            throw new LogicException(sprintf('You are already switched to "%s" user.', $token->getUsername()));
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

        $user = $this->provider->loadUserByUsername($username);
        if ($user instanceof Account && $user->getAllowSupport() === false) {
            throw new AccessDeniedException('User does not allow access to his account (allow_support)');
        }

        $this->userChecker->checkPostAuth($user);

        $roles = $user->getRoles();
        $roles[] = new SwitchUserRole('ROLE_PREVIOUS_ADMIN', $this->tokenStorage->getToken());

        $token = new UsernamePasswordToken($user, $this->providerKey, $roles);

        if ($this->dispatcher instanceof EventDispatcherInterface) {
            $switchEvent = new SwitchUserEvent($request, $token->getUser());
            $this->dispatcher->dispatch($switchEvent);
        }

        return $token;
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
                'username' => $original->getUsername(),
            ]);
        }

        return $original;
    }

    /**
     * Gets the original Token from a switched one.
     *
     * @return TokenInterface|false The original TokenInterface instance, false if the current TokenInterface is not switched
     */
    private function getOriginalToken(?TokenInterface $token)
    {
        if (! $token instanceof TokenInterface) {
            return false;
        }

        foreach ($token->getRoles() as $role) {
            if ($role instanceof SwitchUserRole) {
                return $role->getSource();
            }
        }

        return false;
    }
}

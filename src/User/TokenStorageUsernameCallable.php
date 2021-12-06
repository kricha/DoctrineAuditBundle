<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle\User;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TokenStorageUsernameCallable
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __invoke(): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (null !== $token && $token->getUser() !== null) {
            // @deprecated since Symfony 5.3, change to $token->getUserIdentifier() in 6.0
            $username = \method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername();
            $data  = [$username];
            $user = $token->getUser();
            if ($user && \method_exists($user, 'getId')) {
                $data[] = $user->getId();
            }

            return \implode('|||', $data);
        }

        return null;
    }

    public function setToken(TokenStorageInterface $tokenStorage): void
    {
        $this->tokenStorage = $tokenStorage;
    }
}

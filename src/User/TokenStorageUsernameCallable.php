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
        if (null !== $token && $token->isAuthenticated()) {
            $data  = [$token->getUsername()];
            if ($user = $token->getUser()) {
                if (\method_exists($user, 'getId')) {
                    $data[] = $user->getId();
                }
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

<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle\DBAL\Logging;

use Doctrine\DBAL\Logging\SQLLogger;

class AuditLogger implements SQLLogger
{
    private $flusher;

    public function __construct(callable $flusher)
    {
        $this->flusher = $flusher;
    }

    public function startQuery($sql, array $params = null, array $types = null): void
    {
        if ('"COMMIT"' === $sql) {
            \call_user_func($this->flusher);
        }
    }

    public function stopQuery(): void
    {
    }
}

<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle;

use Doctrine\ORM\Proxy\Proxy;

class Helper
{
    public static function getRealClass($subject): string
    {
        $class = \is_object($subject) ? \get_class($subject) : $subject;
        if (false === $pos = \strrpos($class, '\\'.Proxy::MARKER.'\\')) {
            return $class;
        }

        return \substr($class, $pos + Proxy::MARKER_LENGTH + 2);
    }

    public static function paramToNamespace(string $entity): string
    {
        return \str_replace('-', '\\', $entity);
    }

    public static function namespaceToParam(string $entity): string
    {
        return \str_replace('\\', '-', $entity);
    }
}

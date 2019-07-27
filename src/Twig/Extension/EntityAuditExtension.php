<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle\Twig\Extension;

use Kricha\DoctrineAuditBundle\AuditManager;
use Kricha\DoctrineAuditBundle\Helper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class EntityAuditExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('namespace_to_param', [Helper::class, 'namespaceToParam']),
            new TwigFilter('namespace_to_short_class', [$this, 'namespaceToShortClass']),
            new TwigFilter('audi_label_type', [$this, 'labelType']),
            new TwigFilter('json_decode', 'json_decode'),
        ];
    }

    public function namespaceToShortClass(string $namespace): string
    {
        return \trim(\preg_replace('/(\w+)\\\\+.*\\\\+(\w+)$/', '$1:$2', $namespace));
    }

    public function labelType(string $type): string
    {
        switch ($type) {
            case AuditManager::INSERT:
                $label = 'success';
                break;
            case AuditManager::UPDATE:
                $label = 'primary';
                break;
            case AuditManager::DELETE:
                $label = 'danger';
                break;
            case AuditManager::ASSOCIATE:
            case AuditManager::DISSOCIATE:
                $label = 'warning';
                break;
            default:
                $label = 'secondary';
                break;
        }

        return $label;
    }
}

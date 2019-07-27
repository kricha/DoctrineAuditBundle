<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle;

class AuditConfiguration
{
    private $tablePrefix;

    private $tableSuffix;

    private $ignoredColumns;

    private $entities = [];

    /**
     * @var callable
     */
    private $usernameCallable;

    public function __construct(array $config)
    {
        $this->tablePrefix    = $config['table_prefix'];
        $this->tableSuffix    = $config['table_suffix'];
        $this->ignoredColumns = $config['ignored_columns'];
        if (isset($config['entities']) && !empty($config['entities'])) {
            // use entity names as array keys for easier lookup
            foreach ($config['entities'] as $auditedEntity => $entityOptions) {
                $this->entities[$auditedEntity] = $entityOptions;
            }
        }
    }

    /**
     * Returns true if $entity is audited.
     *
     * @param object|string $entity
     */
    public function isAuditable($entity): bool
    {
        $class = Helper::getRealClass($entity);
        // is $entity part of audited entities?
        if (!\array_key_exists($class, $this->entities)) {
            // no => $entity is not audited
            return false;
        }

        return true;
    }

    /**
     * Returns true if $entity is audited.
     *
     * @param object|string $entity
     */
    public function isAudited($entity): bool
    {
        $class = Helper::getRealClass($entity);
        // is $entity part of audited entities?
        if (!\array_key_exists($class, $this->entities)) {
            // no => $entity is not audited
            return false;
        }

        return true;
    }

    /**
     * Returns true if $field is audited.
     *
     * @param object|string $entity
     */
    public function isAuditedField($entity, string $field): bool
    {
        // is $field is part of globally ignored columns?
        if (\in_array($field, $this->ignoredColumns, true)) {
            // yes => $field is not audited
            return false;
        }
        // is $entity audited?
        if (!$this->isAudited($entity)) {
            // no => $field is not audited
            return false;
        }
        $class         = Helper::getRealClass($entity);

        $entityOptions = $this->entities[$class];
        if (null === $entityOptions) {
            // no option defined => $field is audited
            return true;
        }
        // are columns excluded and is field part of them?
        if (isset($entityOptions['ignored_columns']) &&
            \in_array($field, $entityOptions['ignored_columns'], true)) {
            // yes => $field is not audited
            return false;
        }

        return true;
    }

    /**
     * Get the value of tablePrefix.
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Get the value of tableSuffix.
     */
    public function getTableSuffix(): string
    {
        return $this->tableSuffix;
    }

    /**
     * Get the value of excludedColumns.
     */
    public function getIgnoredColumns(): array
    {
        return $this->ignoredColumns;
    }

    /**
     * Get the value of entities.
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function setUsernameCallable(?callable $usernameCallable): self
    {
        $this->usernameCallable = $usernameCallable;

        return $this;
    }

    public function getCurrentUsername(): string
    {
        $cb = $this->usernameCallable;

        return (string) ($cb ? $cb() : '');
    }
}

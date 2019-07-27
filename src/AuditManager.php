<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

class AuditManager
{
    public const INSERT     = 'INS';
    public const UPDATE     = 'UPD';
    public const DELETE     = 'DEL';
    public const ASSOCIATE  = 'CASC';
    public const DISSOCIATE = 'CDSC';

    private $auditConfiguration;

    private $changes;

    public function __construct(AuditConfiguration $auditConfiguration)
    {
        $this->auditConfiguration = $auditConfiguration;
    }

    public function collectScheduledInsertions(UnitOfWork $uow, EntityManager $em): void
    {
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($this->auditConfiguration->isAudited($entity)) {
                $changeSet       = $uow->getEntityChangeSet($entity);
                $diff            = $this->diff($em, $entity, $changeSet);
                $this->changes[] = [
                    'action' => self::INSERT,
                    'data'   => [
                        $entity,
                        $diff,
                    ],
                ];
            }
        }
    }

    public function collectScheduledUpdates(UnitOfWork $uow, EntityManager $em): void
    {
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->auditConfiguration->isAudited($entity)) {
                $changeSet       = $uow->getEntityChangeSet($entity);
                $diff            = $this->diff($em, $entity, $changeSet);
                $this->changes[] = [
                    'action' => self::UPDATE,
                    'data'   => [
                        $entity,
                        $diff,
                    ],
                ];
            }
        }
    }

    public function collectScheduledDeletions(UnitOfWork $uow, EntityManager $em): void
    {
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($this->auditConfiguration->isAudited($entity)) {
                $uow->initializeObject($entity);
                $id                            = $this->id($em, $entity);
                $this->changes[]               = [
                    'action' => self::DELETE,
                    'data'   => [
                        $entity,
                        $entity,
                        $this->summarize($em, $entity, $id),
                        $id,
                    ],
                ];
            }
        }
    }

    public function collectScheduledCollectionUpdates(UnitOfWork $uow, EntityManager $em): void
    {
        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            if ($this->auditConfiguration->isAudited($collection->getOwner())) {
                $mapping = $collection->getMapping();
                foreach ($collection->getInsertDiff() as $entity) {
                    if ($this->auditConfiguration->isAudited($entity)) {
                        $diff = [
                            'source' => $this->summarize($em, $collection->getOwner()),
                            'target' => $this->summarize($em, $entity),
                        ];
                        if (isset($mapping['joinTable']['name'])) {
                            $data['diff']['table'] = $mapping['joinTable']['name'];
                        }
                        $this->changes[]               = [
                            'action' => self::ASSOCIATE,
                            'data'   => [
                                $collection->getOwner(),
                                $diff,
                            ],
                        ];
                    }
                }
                foreach ($collection->getDeleteDiff() as $entity) {
                    if ($this->auditConfiguration->isAudited($entity)) {
                        $diff = [
                            'source' => $this->summarize($em, $collection->getOwner()),
                            'target' => $this->summarize($em, $entity),
                        ];
                        if (isset($mapping['joinTable']['name'])) {
                            $data['diff']['table'] = $mapping['joinTable']['name'];
                        }
                        $this->changes[]               = [
                            'action' => self::DISSOCIATE,
                            'data'   => [
                                $collection->getOwner(),
                                $diff,
                            ],
                        ];
                    }
                }
            }
        }
    }

    public function collectScheduledCollectionDeletions(UnitOfWork $uow, EntityManager $em): void
    {
        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            if ($this->auditConfiguration->isAudited($collection->getOwner())) {
                $mapping = $collection->getMapping();
                foreach ($collection->toArray() as $entity) {
                    if (!$this->configuration->isAudited($entity)) {
                        continue;
                    }
                    $diff = [
                        'source' => $this->summarize($em, $collection->getOwner()),
                        'target' => $this->summarize($em, $entity),
                    ];
                    if (isset($mapping['joinTable']['name'])) {
                        $data['diff']['table'] = $mapping['joinTable']['name'];
                    }
                    $this->changes[]               = [
                        'action' => self::DISSOCIATE,
                        'data'   => [
                            $collection->getOwner(),
                            $diff,
                        ],
                    ];
                }
            }
        }
    }

    public function diff(EntityManager $em, $entity, array $ch): array
    {
        $meta = $em->getClassMetadata(\get_class($entity));
        $diff = [];
        foreach ($ch as $fieldName => [$old, $new]) {
            $o = null;
            $n = null;
            if ($this->auditConfiguration->isAuditedField($entity, $fieldName)) {
                if (!isset($meta->embeddedClasses[$fieldName]) && $meta->hasField($fieldName)) {
                    $mapping = $meta->fieldMappings[$fieldName];
                    $type    = Type::getType($mapping['type']);
                    $o       = $this->value($em, $type, $old, $mapping);
                    $n       = $this->value($em, $type, $new, $mapping);
                }
                if ($meta->hasAssociation($fieldName) && $meta->isSingleValuedAssociation($fieldName)
                ) {
                    $o = $this->summarize($em, $old);
                    $n = $this->summarize($em, $new);
                }
            }

            if ($o !== $n) {
                $diff[$fieldName] = [
                    'old' => $o,
                    'new' => $n,
                ];
            }
        }

        return $diff;
    }

    public function summarize(EntityManager $em, $entity = null, $id = null): ?array
    {
        if (null === $entity) {
            return null;
        }
        $em->getUnitOfWork()->initializeObject($entity); // ensure that proxies are initialized
        $meta   = $em->getClassMetadata(Helper::getRealClass($entity));
        $pkName = $meta->getSingleIdentifierFieldName();

        $pkValue = $id ?? $this->id($em, $entity);
        if (\method_exists($entity, '__toString')) {
            $label = (string) $entity;
        } else {
            $label = \get_class($entity).'#'.$pkValue;
        }

        return [
            'label' => $label,
            'class' => $meta->name,
            'table' => $meta->getTableName(),
            $pkName => $pkValue,
        ];
    }

    public function id(EntityManager $em, $entity)
    {
        $meta = $em->getClassMetadata(\get_class($entity));
        $pk   = $meta->getSingleIdentifierFieldName();
        if (isset($meta->fieldMappings[$pk])) {
            $type = Type::getType($meta->fieldMappings[$pk]['type']);

            return $this->value($em, $type, $meta->getReflectionProperty($pk)->getValue($entity));
        }
        // Primary key is not part of fieldMapping
        // @see https://github.com/DamienHarper/DoctrineAuditBundle/issues/40
        // @see https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/composite-primary-keys.html#identity-through-foreign-entities
        // We try to get it from associationMapping (will throw a MappingException if not available)
        $targetEntity = $meta->getReflectionProperty($pk)->getValue($entity);
        $mapping      = $meta->getAssociationMapping($pk);
        $meta         = $em->getClassMetadata($mapping['targetEntity']);
        $pk           = $meta->getSingleIdentifierFieldName();
        $type         = Type::getType($meta->fieldMappings[$pk]['type']);

        return $this->value($em, $type, $meta->getReflectionProperty($pk)->getValue($targetEntity));
    }

    public function insert(EntityManager $em, $entity, array $diff): array
    {
        $meta = $em->getClassMetadata(\get_class($entity));

        return $this->audit(
            $em,
            [
                'action'  => self::INSERT,
                'changer' => $this->auditConfiguration->getCurrentUsername(),
                'diff'    => $diff,
                'table'   => $meta->getTableName(),
                'schema'  => $meta->getSchemaName(),
                'id'      => $this->id($em, $entity),
            ]
        );
    }

    public function update(EntityManager $em, $entity, array $diff): array
    {
        if (!$diff) {
            return []; // if there is no entity diff, do not log it
        }
        $meta = $em->getClassMetadata(\get_class($entity));

        return $this->audit(
            $em,
            [
                'action'  => self::UPDATE,
                'changer' => $this->auditConfiguration->getCurrentUsername(),
                'diff'    => $diff,
                'table'   => $meta->getTableName(),
                'schema'  => $meta->getSchemaName(),
                'id'      => $this->id($em, $entity),
            ]
        );
    }

    public function remove(EntityManager $em, $entity, $diff, $id): array
    {
        $meta = $em->getClassMetadata(\get_class($entity));

        return $this->audit(
            $em,
            [
                'action'  => self::DELETE,
                'changer' => $this->auditConfiguration->getCurrentUsername(),
                'diff'    => $diff,
                'table'   => $meta->getTableName(),
                'schema'  => $meta->getSchemaName(),
                'id'      => $id,
            ]
        );
    }

    public function processChanges(EntityManager $em): void
    {
        if ($this->changes) {
            $queries = [];
            foreach ($this->changes as $entityChanges) {
                $action = $entityChanges['action'];
                $data   = $entityChanges['data'];
                switch ($action) {
                    case self::INSERT:
                        $queries[] = $this->insert($em, $data[0], $data[1]);
                        break;
                    case self::UPDATE:
                        $queries[] = $this->update($em, $data[0], $data[1]);
                        break;
                    case self::DELETE:
                        $queries[] = $this->remove($em, $data[0], $data[1], $data[2]);
                        break;
                    case self::ASSOCIATE:
                    case self::DISSOCIATE:
                        $queries[] = $this->toggleAssociation($action, $em, $data[0], $data[1]);
                        break;
                }
            }

            $em->getConnection()->transactional(function (Connection $connection) use ($queries): void {
                foreach ($queries as $query) {
                    $stmt = $connection->prepare($query[0]);
                    $stmt->execute($query[1]);
                }
            });
        }
    }

    public function getAuditConfiguration(): AuditConfiguration
    {
        return $this->auditConfiguration;
    }

    private function toggleAssociation(string $type, EntityManager $em, $entity, array $diff): array
    {
        $meta = $em->getClassMetadata(\get_class($entity));
        $data = [
            'action'  => $type,
            'changer' => $this->auditConfiguration->getCurrentUsername(),
            'diff'    => $diff,
            'table'   => $meta->getTableName(),
            'schema'  => $meta->getSchemaName(),
            'id'      => $this->id($em, $entity),
        ];

        return $this->audit($em, $data);
    }

    private function audit(EntityManager $em, array $data): array
    {
        $schema     = $data['schema'] ? $data['schema'].'.' : '';
        $auditTable = $schema.$this->auditConfiguration->getTablePrefix(
            ).$data['table'].$this->auditConfiguration->getTableSuffix();
        $fields = [
            'type'       => ':type',
            'object_id'  => ':object_id',
            'diff'       => ':diff',
            'changer'    => ':changer',
            'created_at' => ':created_at',
        ];
        $query = \sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $auditTable,
            \implode(', ', \array_keys($fields)),
            \implode(', ', \array_values($fields))
        );

        $dt     = new \DateTime('now');
        $params = [
            'type'       => $data['action'],
            'object_id'  => (string) $data['id'],
            'diff'       => \json_encode($data['diff']),
            'changer'    => $data['changer'],
            'created_at' => $dt->format('Y-m-d H:i:s'),
        ];

        return [$query, $params];
    }

    private function value(EntityManager $em, Type $type, $value, $mapping = [])
    {
        if (null === $value) {
            return null;
        }
        $platform = $em->getConnection()->getDatabasePlatform();
        switch ($type->getName()) {
            case Type::DECIMAL:
                if ($mapping) {
                    $convertedValue = \number_format((float) $value, $mapping['scale'], '.', '');
                    break;
                }
            // no break
            case Type::BIGINT:
                $convertedValue = (string) $value;
                break;
            case Type::INTEGER:
            case Type::SMALLINT:
                $convertedValue = (int) $value;
                break;
            case Type::FLOAT:
            case Type::BOOLEAN:
                $convertedValue = $type->convertToPHPValue($value, $platform);
                break;
            default:
                $convertedValue = $type->convertToDatabaseValue($value, $platform);
        }

        return $convertedValue;
    }
}

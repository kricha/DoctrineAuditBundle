<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Kricha\DoctrineAuditBundle\AuditManager;

class SchemaAuditSubscriber implements EventSubscriber
{
    private const AUDIT_TABLE_COLUMNS = [
        'id'         => [
            'type'    => Types::INTEGER,
            'options' => [
                'autoincrement' => true,
                'unsigned'      => true,
            ],
        ],
        'type'       => [
            'type'    => Types::STRING,
            'options' => [
                'notnull' => true,
                'length'  => 10,
            ],
        ],
        'object_id'  => [
            'type'    => Types::STRING,
            'options' => [
                'notnull' => true,
            ],
        ],
        'diff'       => [
            'type'    => Types::JSON_ARRAY,
            'options' => [
                'default' => null,
                'notnull' => false,
            ],
        ],
        'changer'    => [
            'type'    => Types::STRING,
            'options' => [
                'default' => null,
                'notnull' => false,
                'length'  => 255,
            ],
        ],
        'created_at' => [
            'type'    => Types::DATETIME,
            'options' => [
                'notnull' => true,
            ],
        ],
    ];

    private const AUDIT_TABLE_INDICES = [
        'id'         => [
            'type' => 'primary',
        ],
        'type'       => [
            'type' => 'index',
        ],
        'object_id'  => [
            'type' => 'index',
        ],
        'changer'    => [
            'type' => 'index',
        ],
        'created_at' => [
            'type' => 'index',
        ],
    ];

    private $manager;

    public function __construct(AuditManager $manager)
    {
        $this->manager = $manager;
    }

    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs): void
    {
        $cm = $eventArgs->getClassMetadata();
        if (!$this->manager->getAuditConfiguration()->isAudited($cm->name)) {
            $audited = false;
            if ($cm->rootEntityName === $cm->name && ($cm->isInheritanceTypeJoined(
                    ) || $cm->isInheritanceTypeSingleTable())) {
                foreach ($cm->subClasses as $subClass) {
                    if ($this->manager->getConfiguration()->isAudited($subClass)) {
                        $audited = true;
                    }
                }
            }
            if (!$audited) {
                return;
            }
        }
        if (!\in_array(
            $cm->inheritanceType,
            [
                ClassMetadataInfo::INHERITANCE_TYPE_NONE,
                ClassMetadataInfo::INHERITANCE_TYPE_JOINED,
                ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE,
            ],
            true
        )) {
            throw new \RuntimeException("Inheritance type \"{$cm->inheritanceType}\" is not yet supported");
        }

        $schema         = $eventArgs->getSchema();
        $table          = $eventArgs->getClassTable();
        $auditTableName = \preg_replace(
            \sprintf('#^([^\.]+\.)?(%s)$#', \preg_quote($table->getName(), '#')),
            \sprintf(
                '$1%s$2%s',
                \preg_quote($this->manager->getAuditConfiguration()->getTablePrefix(), '#'),
                \preg_quote($this->manager->getAuditConfiguration()->getTableSuffix(), '#')
            ),
            $table->getName()
        );

        if (null !== $auditTableName && !$schema->hasTable($auditTableName)) {
            $auditTable = $schema->createTable($auditTableName);
            foreach (self::AUDIT_TABLE_COLUMNS as $name => $struct) {
                $auditTable->addColumn($name, $struct['type'], $struct['options']);
            }
            $auditTableNameMD5 = \md5($auditTableName);
            foreach (self::AUDIT_TABLE_INDICES as $column => $struct) {
                if ('primary' === $struct['type']) {
                    $auditTable->setPrimaryKey([$column]);
                } else {
                    $auditTable->addIndex([$column], "${column}_${auditTableNameMD5}_idx");
                }
            }
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            ToolEvents::postGenerateSchemaTable,
        ];
    }
}

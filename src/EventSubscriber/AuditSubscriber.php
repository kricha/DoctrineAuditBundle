<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Logging\LoggerChain;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Kricha\DoctrineAuditBundle\AuditManager;
use Kricha\DoctrineAuditBundle\DBAL\Logging\AuditLogger;

class AuditSubscriber implements EventSubscriber
{
    private $manager;

    public function __construct(AuditManager $manager)
    {
        $this->manager = $manager;
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $this->manager->collectScheduledUpdates($uow, $em);
        $this->manager->collectScheduledInsertions($uow, $em);
        $this->manager->collectScheduledDeletions($uow, $em);
        $this->manager->collectScheduledCollectionDeletions($uow, $em);
        $this->manager->collectScheduledCollectionUpdates($uow, $em);

        $defaultLogger = $em->getConnection()->getConfiguration()->getSQLLogger();
        $auditLogger = new AuditLogger(function () use ($em): void {
            $this->manager->processChanges($em);
            $this->manager->resetChangeset();
        });
        
        $loggerChain = new LoggerChain([$defaultLogger, $auditLogger]);
        $em->getConnection()->getConfiguration()->setSQLLogger($loggerChain);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [Events::onFlush, 'onFlush'];
    }
}

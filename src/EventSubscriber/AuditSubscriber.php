<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Logging\LoggerChain;
use Doctrine\DBAL\Logging\SQLLogger;
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
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $this->manager->collectScheduledUpdates($uow, $em);
        $this->manager->collectScheduledInsertions($uow, $em);
        $this->manager->collectScheduledDeletions($uow, $em);
        $this->manager->collectScheduledCollectionDeletions($uow, $em);
        $this->manager->collectScheduledCollectionUpdates($uow, $em);

        $this->loggerBackup = $em->getConnection()->getConfiguration()->getSQLLogger();
        $loggerChain        = new LoggerChain();
        $loggerChain->addLogger(new AuditLogger(function () use ($em): void {
            $em->getConnection()->getConfiguration()->setSQLLogger($this->loggerBackup);
            $this->manager->processChanges($em);
            $this->manager->resetChangeset();
        }));

        if ($this->loggerBackup instanceof SQLLogger) {
            $loggerChain->addLogger($this->loggerBackup);
        }

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

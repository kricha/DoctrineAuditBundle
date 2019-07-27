<?php

declare(strict_types=1);

/*
 * DoctrineAuditBundle
 */

namespace Kricha\DoctrineAuditBundle\Controller;

use Kricha\DoctrineAuditBundle\Helper;
use Kricha\DoctrineAuditBundle\Reader\AuditReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuditController extends AbstractController
{
    /**
     * @Route("/audit", name="kricha_doctrine_entity_audit_list", methods={"GET"})
     */
    public function listAuditsAction(AuditReader $auditReader): Response
    {
        $auditedEntities = $auditReader->getEntities();

        return $this->render('@KrichaDoctrineAudit/Audit/audits.html.twig', [
            'audited' => $auditReader->getEntities(),
            'reader'  => $auditReader,
        ]);
    }

    /**
     * @Route("/audit/{entity}/{id}", name="kricha_doctrine_entity_audit_history", methods={"GET"})
     *
     * @param int|string $id
     */
    public function showEntityHistoryAction(Request $request, AuditReader $auditReader, string $entity, $id = null): Response
    {
        $page   = (int) $request->query->get('page', 1);
        $entity = Helper::paramToNamespace($entity);

        $entries = $auditReader->getAuditsPager($entity, $id, $page, AuditReader::PAGE_SIZE);

        return $this->render('@KrichaDoctrineAudit/Audit/entity_history.html.twig', [
            'id'      => $id,
            'entity'  => $entity,
            'entries' => $entries,
        ]);
    }

    /**
     * @Route("/audit/details/{entity}/{id}", name="kricha_doctrine_entity_audit_entry", methods={"GET"})
     *
     * @param int|string $id
     */
    public function showAuditEntryAction(AuditReader $auditReader, string $entity, $id): Response
    {
        $entity = Helper::paramToNamespace($entity);
        $data   = $auditReader->getAudit($entity, $id);

        return $this->render('@KrichaDoctrineAudit/Audit/entity_history_entry.html.twig', [
            'entity' => $entity,
            'entry'  => $data[0],
        ]);
    }
}

<?php

namespace Networkteam\SolrCommands\Command;

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\System\Solr\Document\Document;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanupIndexCommand extends Command
{
    public function configure()
    {
        $this->setDescription('Delete documents in index which are not in the index queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootPageItemTypeMap = $this->getRootPageItemTypeMap();

        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);

        foreach($rootPageItemTypeMap as $item) {
            $rootPage = $item['root'];
            $itemType = $item['item_type'];

            $site = $siteRepository->getSiteByPageId($rootPage);
            $indexQueueItems = $this->getItemsByRootPageAndType($rootPage, $itemType);

            /** @var SolrConnection[] $solrServers */
            $solrServers = GeneralUtility::makeInstance(ConnectionManager::class)->getConnectionsBySite($site);

            /** @var Query $query */
            $query = GeneralUtility::makeInstance(Query::class);
            $query->setQuery(sprintf('type:%s AND siteHash:%s',
                $itemType,
                $site->getSiteHash(),
            ));
            $query->setFields(['uid']);

            foreach ($solrServers as $server) {
                $response = $server->getReadService()->search($query);
                $numFound = $response->getParsedData()->response->numFound;
                if ($numFound == 0) {
                    continue;
                }

                $documentUidsToDelete = [];
                $i = 0;
                $pages = ceil($numFound / 1000);
                while ($i <= $pages) {
                    $query->addParam('start', $i == 0 ? 1 : $i * 1000 + 1);
                    $query->addParam('rows', 1000);
                    $response = $server->getReadService()->search($query);

                    /** @var Document[] $documents */
                    $documents = $response->getParsedData()->response->docs;

                    foreach($documents ?? [] as $document) {
                        $uid = $document->getFields()['uid'];
                        if (!in_array($uid, $indexQueueItems)) {
                            $documentUidsToDelete[] = $uid;
                        }
                    }

                    $i++;
                }

                foreach ($documentUidsToDelete as $uid) {
                    $deleteQuery = sprintf('type:%s AND siteHash:%s AND uid:%s',
                        $itemType,
                        $site->getSiteHash(),
                        $uid
                    );

                    if ($output->isVerbose()) {
                        $output->writeln(sprintf('Deleting uid:%d (type: %s, root page: %d)', $uid, $itemType, $rootPage));
                    }

                    $server->getWriteService()->deleteByQuery($deleteQuery);
                }

                $server->getWriteService()->commit();
            }
        }

        return 0;
    }

    protected function getRootPageItemTypeMap(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $map = $queryBuilder
            ->select('root', 'item_type')
            ->from('tx_solr_indexqueue_item')
            ->groupBy('root', 'item_type')
            ->execute()
            ->fetchAll();

        return $map;
    }

    protected function getItemsByRootPageAndType($rootPage, $type): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $query = $queryBuilder
            ->select('item_uid')
            ->from('tx_solr_indexqueue_item')
            ->where(
                $queryBuilder->expr()->eq('root', $queryBuilder->createNamedParameter($rootPage, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('item_type', $queryBuilder->createNamedParameter($type))
            )
            ->execute();

        $items = [];
        while ($row = $query->fetch()) {
            $items[] = $row['item_uid'];
        }

        return $items;
    }
}

<?php

namespace Networkteam\SolrCommands\Command;

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClearIndexCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Clear index')
        ->addArgument('rootpage', InputArgument::REQUIRED, 'The root page id')
        ->addArgument('type', InputArgument::OPTIONAL, 'A type. Normally a table name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);

        $site = $siteRepository->getSiteByPageId($input->getArgument('rootpage'));
        if (!$site instanceof Site) {
            $output->writeln('Site not found');
            return 1;
        }

        /** @var ConnectionManager $connectionManager */
        $connectionManager = GeneralUtility::makeInstance(ConnectionManager::class);
        /** @var SolrConnection $connection*/
        foreach ($connectionManager->getConnectionsBySite($site) as $connection) {
            if ($type = $input->getArgument('type')) {
                $connection->getWriteService()->deleteByType($type);
            } else {
                $connection->getWriteService()->deleteByQuery('*:*');
            }
            $connection->getWriteService()->commit();
        }

        return 0;
    }
}

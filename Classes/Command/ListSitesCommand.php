<?php

namespace Networkteam\SolrCommands\Command;

use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ListSitesCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('List sites');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);

        foreach ($sites = $siteRepository->getAvailableSites() as $site) {
            $output->writeln($site->getLabel());
        }

        return 0;
    }
}

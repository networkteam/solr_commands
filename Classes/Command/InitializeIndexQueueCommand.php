<?php

namespace Networkteam\SolrCommands\Command;

use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InitializeIndexQueueCommand extends Command
{
    public function configure()
    {
        $this->addArgument('rootpage', InputArgument::REQUIRED, 'site root page id');
        $this->addArgument('type', InputArgument::OPTIONAL, 'List of indexing configurations. Leave empty for all')
            ->setDescription('Initialize IndexQueue by type and site');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);
        $site = $siteRepository->getSiteByPageId((integer)$input->getArgument('rootpage'));

        $indexingConfigurations = GeneralUtility::trimExplode(',', $input->getArgument('type') ?? '*');

        /** @var Queue $indexQueue */
        $indexQueue = GeneralUtility::makeInstance(Queue::class);
        $initializationStatus = $indexQueue->getInitializationService()->initializeBySiteAndIndexConfigurations($site, $indexingConfigurations);

        // Even unknown configurations return true. This should better be fixed in EXT:solr.
        $wasSuccesful = 0;
        foreach ($initializationStatus as $name => $status) {
            if($status === false) {
                $wasSuccesful = 1;
                $output->writeln(sprintf('Failed for configuration %s', $name));
            }
        }

        return $wasSuccesful;
    }
}

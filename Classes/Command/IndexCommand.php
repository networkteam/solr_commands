<?php

namespace Networkteam\SolrCommands\Command;

use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\Task\IndexQueueWorkerTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IndexCommand extends Command
{
    public function configure()
    {
        $this->addArgument('rootpage', InputArgument::OPTIONAL, 'Site root page id', '*');
        $this->addArgument('limit', InputArgument::OPTIONAL, 'Limit documents per site', PHP_INT_MAX)
            ->setDescription('Index documents of all sites');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);

        if (($input->getArgument('rootpage') ?? '*') == '*') {
            $sites = $siteRepository->getAvailableSites();

        } else {
            $sites[] = $siteRepository->getSiteByPageId((integer)$input->getArgument('rootpage'));
        }

        $oneTaskFailed = false;
        foreach ($sites as $site) {
            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('Indexing %s (Root page %d)', $site->getDomain(), $site->getRootPageId()));
            }

            $indexTask = GeneralUtility::makeInstance(IndexQueueWorkerTask::class);
            $indexTask->setRootPageId($site->getRootPageId());
            $indexTask->setDocumentsToIndexLimit((integer)$input->getArgument('limit'));

            if (!$indexTask->execute()) {
                $oneTaskFailed = true;
                $output->writeln(sprintf('Failed indexing site %d', $site->getRootPageId()));
            }
        }

        return $oneTaskFailed === true ? Command::FAILURE : Command::SUCCESS;
    }
}

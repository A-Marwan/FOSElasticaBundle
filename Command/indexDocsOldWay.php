<?php



namespace FOS\ElasticaBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Elastica\Query;
use Elastica\Result;

class IndexDocumentCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('indexation:document:all')
            ->addOption('remove-before', 'f', InputOption::VALUE_NONE, 'Delete before update')
            ->addOption('update-image', 'u', InputOption::VALUE_NONE, 'Update image field')
            ->addOption('ignore-image', 'i', InputOption::VALUE_NONE, 'ignore image field')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('remove-before')) {
            $this->get('library.indexation')->deleteIndexes();
            foreach (\LibraryDocumentCollectionPeer::doSelect(new \Criteria()) as $documentCollection) {
                $this->get('library.documentCollection')->map($this->get('library.documentCollection')->export($documentCollection));
            }
        }
        $vocabularyService = $this->get('library.vocabulary');
        foreach ($this->get('library.documentCollection')->getList() as $documentCollection) {
            $documents = $documentCollection->getLibraryDocuments();
            if (count($documents)) {
                $output->writeln($documentCollection->getName() . ':');
                $progress = new ProgressBar($output, count($documents));
                $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
                foreach ($documents as $document) {
                    $elasticaDocument = $this
                        ->get('library.indexation')
                        ->getDocument($document->getId());
                    if (false !== $elasticaDocument && !$input->getOption('update-image')) {
                        $this
                            ->get('library.indexation')
                            ->refreshDocument($document, $vocabularyService, false);
                    }
                    else {
                        $this
                            ->get('library.indexation')
                            ->refreshDocument($document, $vocabularyService, !$input->getOption('ignore-image'));
                    }
                    $progress->advance();
                }
                $progress->finish();
                $output->writeln("\n");
            }
        }
    }
}

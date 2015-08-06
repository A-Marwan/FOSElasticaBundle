<?php


namespace FOS\ElasticaBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Elastica\Query;
use Elastica\Result;

class IndexVocabularyCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('indexation:vocabulary:all');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->get('library.vocabulary')
            ->createAutocompleterIndexes();
        foreach (\sfConfig::get('app_accepted_languages') as $language) {
            $index = $this->get('elasticsearch')->getIndex($this->get('library.indexation')->generateElasticIndexName($language, 'vocabulary_autocomplete'));
            if ($index->exists()) {
                $index->delete();
            }
            foreach ($this
                         ->get('library.vocabulary')
                         ->setLanguage($language)
                         ->getList() as $vocabulary) {
                $this
                    ->get('library.vocabulary')
                    ->regenerateAutompleteIndex($vocabulary);
            }
        }
    }
}

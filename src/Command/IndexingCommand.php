<?php

namespace App\Command;

use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\OpenSearch\OpenSearchVectorStore;
use LLPhant\OllamaConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;

#[AsCommand(
    name: 'app:indexing',
    description: 'Add a short description for your command',
)]
class IndexingCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');


        $reader = new FileDataReader($this->projectDir . '/files');
        $documents = $reader->getDocuments();

        $io->info('splitting files into chunks...');
        $splitDocuments = DocumentSplitter::splitDocuments($documents, 512, '.', 128);


        $io->info('embedding...');
        $embeddingConfig = new OllamaConfig();
        $embeddingConfig->model = 'bge-m3';
        $embeddingConfig->url = 'http://host.docker.internal:11434/api/';

        $embeddingGenerator = new OllamaEmbeddingGenerator($embeddingConfig);
        $embeddingGenerator->embedDocuments($splitDocuments);
            
        $opensearchClient = ClientBuilder::create()
            ->setHosts(['http://opensearch:9200'])
            ->build();

        $store = new OpenSearchVectorStore($opensearchClient);

        $store->addDocuments($splitDocuments);

        $io->success('Index built successfully');

        return Command::SUCCESS;
    }
}

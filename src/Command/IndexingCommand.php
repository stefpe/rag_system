<?php

namespace App\Command;

use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\OpenSearch\OpenSearchVectorStore;
use LLPhant\OllamaConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:indexing',
    description: 'Index files',
)]
class IndexingCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private OpenSearchVectorStore $vectorStore,
        #[Autowire(service:'embedding_config')]
        private OllamaConfig $embeddingConfig
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $reader = new FileDataReader($this->projectDir . '/files');
        $documents = $reader->getDocuments();

        $io->info('splitting files into chunks...');
        $splitDocuments = DocumentSplitter::splitDocuments($documents, 512, '.', 128);

        $io->info('embedding...');
        $embeddingGenerator = new OllamaEmbeddingGenerator($this->embeddingConfig);
        $embeddingGenerator->embedDocuments($splitDocuments);

        $io->info('indexing...');
        $this->vectorStore->addDocuments($splitDocuments);

        $io->success('Index built successfully');

        return Command::SUCCESS;
    }
}

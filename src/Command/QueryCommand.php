<?php

namespace App\Command;

use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\OpenSearch\OpenSearchVectorStore;
use LLPhant\OllamaConfig;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:query',
    description: 'Add a short description for your command',
)]
class QueryCommand extends Command
{
    public function __construct(
        #[Autowire('%querySystemTemplate%')]
        private string $querySystemTemplate,
        private OpenSearchVectorStore $vectorStore
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('question', InputArgument::OPTIONAL, 'Question to ask')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $embeddingConfig = new OllamaConfig();
        $embeddingConfig->model = 'bge-m3';
        $embeddingConfig->url = 'http://host.docker.internal:11434/api/';

        $embeddingGenerator = new OllamaEmbeddingGenerator($embeddingConfig);

        $chatConfig = new OllamaConfig();
        $chatConfig->model = 'llama3:latest';
        $chatConfig->url = 'http://host.docker.internal:11434/api/';

        $qa = new QuestionAnswering(
            $this->vectorStore,
            $embeddingGenerator,
            new OllamaChat($chatConfig)
        );

        $qa->systemMessageTemplate = $this->querySystemTemplate;

        $answer = $qa->answerQuestion($input->getArgument('question'));

        $io->success($answer);

        return Command::SUCCESS;
    }
}

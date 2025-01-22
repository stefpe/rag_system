<?php

namespace App\Command;

use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\OpenSearch\OpenSearchVectorStore;
use LLPhant\OllamaConfig;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        private OpenSearchVectorStore $vectorStore,
        #[Autowire(service:'embedding_config')]
        private OllamaConfig $embeddingConfig,
        #[Autowire(service:'chat_config')]
        private OllamaConfig $chatConfig
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('question', InputArgument::REQUIRED, 'Question to ask')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $qa = new QuestionAnswering(
            $this->vectorStore,
            new OllamaEmbeddingGenerator($this->embeddingConfig),
            new OllamaChat($this->chatConfig)
        );

        $qa->systemMessageTemplate = $this->querySystemTemplate;
        $question = $input->getArgument('question');

        $answer = $qa->answerQuestion($question, 4, ['query' => $question]);

        $io->success($answer);

        return Command::SUCCESS;
    }
}

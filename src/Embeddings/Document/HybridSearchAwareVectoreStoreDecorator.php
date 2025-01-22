<?php


namespace App\Embeddings\Document;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\VectorStores\OpenSearch\OpenSearchVectorStore;
use OpenSearch\Client;

class HybridSearchAwareVectoreStoreDecorator extends OpenSearchVectorStore
{
    public function __construct(
        private readonly OpenSearchVectorStore $openSearchVectorStore,
        Client $client
    ) {
        parent::__construct($client);
    }

    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $searchParams = [
            'index' => $this->indexName,
            'search_pipeline' => 'nlp-search-pipeline',
            'body' => [
                'query' => [
                    "hybrid" => [
                        "queries" => [
                            [
                                "match" => [
                                    "content" => [
                                        "query" => $additionalArguments['query'],
                                    ]
                                ],
                            ],
                            [
                                "knn" => [
                                    "embedding" => [
                                        "vector" => $embedding,
                                        "k" => $k
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
                'sort' => [
                    [
                        '_score' => [
                            'order' => 'desc',
                        ],
                    ],
                ],
            ],
        ];

        $rawResponse = $this->client->search($searchParams);

        $documents = [];

        foreach ($rawResponse['hits']['hits'] as $hit) {
            $document = new Document();
            $document->embedding = $hit['_source']['embedding'];
            $document->content = $hit['_source']['content'];
            $document->formattedContent = $hit['_source']['formattedContent'];
            $document->sourceType = $hit['_source']['sourceType'];
            $document->sourceName = $hit['_source']['sourceName'];
            $document->hash = $hit['_source']['hash'];
            $document->chunkNumber = $hit['_source']['chunkNumber'];
            $documents[] = $document;
        }

        return $documents;
    }
}

<?php

namespace App\Embeddings\Document;

use LLPhant\Query\SemanticSearch\RetrievedDocumentsTransformer;

class MissingContextAwareHandler implements RetrievedDocumentsTransformer
{
    public function transformDocuments(array $questions, array $retrievedDocs): array
    {
        if (count($retrievedDocs) > 0) {
            return $retrievedDocs;
        }

        return $this->handleMissingContext($questions);
    }

    private function handleMissingContext(array $questions): array
    {
        // handle missing context
    }
}
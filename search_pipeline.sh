curl -XPUT "http://opensearch:9200/_search/pipeline/nlp-search-pipeline" -H 'Content-Type: application/json' -d'
{
  "description": "Post processor for hybrid search",
  "phase_results_processors": [
    {
      "normalization-processor": {
        "normalization": {
          "technique": "min_max"
        },
        "combination": {
          "technique": "harmonic_mean",
          "parameters": {
            "weights": [
              0.3,
              0.7
            ]
          }
        }
      }
    }
  ]
}
'
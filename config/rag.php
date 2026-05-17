<?php

return [
    'embedding_model' => env('RAG_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'embedding_dimensions' => env('RAG_EMBEDDING_DIMENSIONS') ? (int) env('RAG_EMBEDDING_DIMENSIONS') : null,
    'chat_model' => env('RAG_CHAT_MODEL', 'gpt-5.4-mini'),

    'chunk_size' => (int) env('RAG_CHUNK_SIZE', 3000),
    'chunk_overlap' => (int) env('RAG_CHUNK_OVERLAP', 400),

    'top_k' => (int) env('RAG_TOP_K', 5),
    'min_score' => (float) env('RAG_MIN_SCORE', 0.35),
    'suggestion_min_score' => (float) env('RAG_SUGGESTION_MIN_SCORE', 0.2),
    'suggestion_fallback_min_score' => (float) env('RAG_SUGGESTION_FALLBACK_MIN_SCORE', 0.05),
    'max_candidates' => (int) env('RAG_MAX_CANDIDATES', 2000),
];

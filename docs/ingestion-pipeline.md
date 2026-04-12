# Ingestion Pipeline

The ingestion pipeline takes raw files or text and turns them into searchable vector embeddings.

## How It Works

```
Input (file or text)
  --> Validate (type, size)
  --> Store file (local disk or S3)
  --> Parse to plain text (TextParser, PdfParser, DocxParser)
  --> Chunk with overlap (configurable size)
  --> Generate embeddings (OpenAI)
  --> Upsert to vector store (Pinecone or pgvector)
  --> Done (state: "indexed")
```

## Ingest Text

```php
use LarAIgent\AiKit\Services\Ingestion\IngestionService;

$ingestion = app(IngestionService::class);
$asset = $ingestion->ingestText('Your company policies and FAQ content here...');
```

## Ingest a File

```php
$asset = $ingestion->ingestFile($request->file('document'));
```

## Pipeline States

Each ingestion is tracked in the `ai_ingestions` table:

| State | Description |
|---|---|
| `queued` | Job dispatched, waiting to process |
| `parsing` | Extracting text from file |
| `chunking` | Splitting text into overlapping chunks |
| `embedding` | Generating vector embeddings |
| `indexed` | Complete - chunks are searchable |
| `failed` | Error occurred (check `error` column) |

## Check Ingestion Status

```php
$asset = Asset::find($id);
$state = $asset->ingestion->state;  // "indexed", "failed", etc.
$error = $asset->ingestion->error;  // null or error message
$chunks = $asset->ingestion->chunk_count;
```

## Queue Processing

By default (`QUEUE_CONNECTION=sync`), ingestion runs inline during the request. For production:

```env
QUEUE_CONNECTION=database
```

Then run the queue worker:

```bash
php artisan queue:work
```

## Supported File Types

| Type | Mime | Required Package |
|---|---|---|
| Plain text (.txt, .md, .csv) | text/plain, text/markdown, text/csv | Built-in |
| PDF (.pdf) | application/pdf | `composer require smalot/pdfparser` |
| Word (.docx) | application/vnd.openxmlformats-... | `composer require phpoffice/phpword` |

## Chunking Configuration

```env
LARAI_CHUNK_SIZE=512     # Words per chunk
LARAI_CHUNK_OVERLAP=50   # Overlapping words between chunks
```

Smaller chunks = more precise retrieval but more API calls.
Larger chunks = more context per result but less precision.

## Database Tables

| Table | Purpose |
|---|---|
| `ai_documents` | Searchable documents with embeddings (used by SimilaritySearch) |
| `ai_assets` | File metadata (name, path, mime, size, checksum) |
| `ai_chunks` | Text chunks linked to assets |
| `ai_ingestions` | Pipeline state tracking per asset |

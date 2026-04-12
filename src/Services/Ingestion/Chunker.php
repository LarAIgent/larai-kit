<?php

namespace LarAIgent\AiKit\Services\Ingestion;

class Chunker
{
    protected int $chunkSize;
    protected int $overlap;

    public function __construct(?int $chunkSize = null, ?int $overlap = null)
    {
        $this->chunkSize = $chunkSize ?? (int) config('larai-kit.chunk_size', 512);
        $this->overlap = $overlap ?? (int) config('larai-kit.chunk_overlap', 50);
    }

    /**
     * Split text into overlapping chunks.
     *
     * @return array<int, array{text: string, chunk_index: int}>
     */
    public function chunk(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $words = preg_split('/\s+/', $text);
        $totalWords = count($words);

        if ($totalWords <= $this->chunkSize) {
            return [
                ['text' => $text, 'chunk_index' => 0],
            ];
        }

        $chunks = [];
        $index = 0;
        $start = 0;

        while ($start < $totalWords) {
            $end = min($start + $this->chunkSize, $totalWords);
            $chunkWords = array_slice($words, $start, $end - $start);
            $chunkText = implode(' ', $chunkWords);

            if (trim($chunkText) !== '') {
                $chunks[] = [
                    'text' => $chunkText,
                    'chunk_index' => $index,
                ];
                $index++;
            }

            if ($end >= $totalWords) {
                break;
            }

            $start = $end - $this->overlap;
        }

        return $chunks;
    }
}

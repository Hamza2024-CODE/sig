<?php

namespace App\Domains\Reporting\Contracts;

/**
 * ReportEngineInterface
 *
 * Contract that all report output engines must implement.
 * Engines receive a PHP Generator (lazy data source) and stream
 * their output directly to the HTTP response — never to disk.
 *
 * The Generator pattern guarantees O(1) memory regardless of dataset size:
 * each row is fetched, emitted to the output buffer, and immediately garbage-collected.
 */
interface ReportEngineInterface
{
    /**
     * Stream report data to the HTTP output buffer.
     *
     * @param  \Generator $data    Lazy row generator from the Repository
     * @param  array      $columns Column definitions: [ 'key' => 'Label AR / Label FR' ]
     * @param  array      $meta    Report metadata: title, generated_by, generated_at, filters
     */
    public function stream(\Generator $data, array $columns, array $meta): void;
}

<?php

namespace App\Console\Commands;

use App\Services\NexaflowClient;
use Illuminate\Console\Command;
use Throwable;

class FetchNexaflowPage extends Command
{
    /** @var string */
    protected $signature = 'nexaflow:fetch-page
                            {pageId : Nexaflow page id}
                            {--websiteId= : Override NEXAFLOW_WEBSITE_ID for this call}';

    /** @var string */
    protected $description = 'Fetch a Nexaflow CMS page via NexaflowClient and print the JSON (no API key is printed)';

    public function handle(NexaflowClient $client): int
    {
        $pageId    = (string) $this->argument('pageId');
        $websiteId = $this->option('websiteId') ?: null;

        try {
            $payload = $client->page($pageId, $websiteId);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}

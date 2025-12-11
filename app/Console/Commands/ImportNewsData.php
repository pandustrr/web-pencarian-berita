<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\News;
use Illuminate\Support\Facades\DB;

class ImportNewsData extends Command
{
    protected $signature = 'import:news {--chunk=1000 : Number of records to insert per batch}';
    protected $description = 'Import news data from CSV file into database';

    public function handle()
    {
        $csvPath = base_path('python_app/preprocessed_news.csv');

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: $csvPath");
            return Command::FAILURE;
        }

        $this->info('Starting import...');

        $chunkSize = (int) $this->option('chunk');
        $totalInserted = 0;
        $chunk = [];

        try {
            $file = fopen($csvPath, 'r');
            $header = fgetcsv($file);

            if (!$header || count($header) !== 3) {
                $this->error('Invalid CSV header');
                fclose($file);
                return Command::FAILURE;
            }

            // Progress bar
            $bar = $this->output->createProgressBar();
            $bar->start();

            while (($row = fgetcsv($file)) !== FALSE) {
                if (count($row) === count($header)) {
                    $chunk[] = [
                        'title' => 'Berita #' . (count($chunk) + $totalInserted + 1),
                        'original_text' => $row[0] ?? '',
                        'translated_text' => $row[1] ?? '',
                        'processed_text' => $row[2] ?? '',
                        'category' => 'Berita',
                        'source' => 'Web Berita',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($chunk) >= $chunkSize) {
                        DB::table('news')->insert($chunk);
                        $totalInserted += count($chunk);
                        $chunk = [];
                        $bar->advance($chunkSize);
                    }
                }
            }

            // Insert remaining records
            if (!empty($chunk)) {
                DB::table('news')->insert($chunk);
                $totalInserted += count($chunk);
                $bar->advance(count($chunk));
            }

            fclose($file);
            $bar->finish();

            $this->newLine();
            $this->info("Successfully imported {$totalInserted} news records!");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

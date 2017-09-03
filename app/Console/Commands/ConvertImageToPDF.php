<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use AlfredNutileInc\Incomings\Log;

class ConvertImageToPDF extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:convert_image_to_pdf {--image-url=} {--destination=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will convert the image and place it on S3 when done.' .
                                'Destination be "bucket-name/folder"';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(\App\ConvertImageToPDF $converter)
    {
        try {
            $url = $this->option('image-url');
            $destination = $this->option('destination');
            $this->info(sprintf("Getting image to convert %s", $url));
            $converter->handle($url, $destination);
            $this->info(
                sprintf(
                    "File Created %s",
                    $converter->getDestinationPath() . "/" .
                    $converter->getRandomFolderId() . "/" .
                    $converter->getFileName() . ".pdf"
                )
            );
        } catch (\Exception $e) {
            Log::info("Error creating file");
            Log::info($e->getMessage());
            $this->error("Error creating file");
            $this->error($e->getMessage());
        }
    }
}

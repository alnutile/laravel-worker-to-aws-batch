<?php

namespace App\Console\Commands;

use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CreateManyFakeImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:fake_images {--total=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make some fake images';

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
    public function handle()
    {
        $count = 0;
        while ($count < $this->option('total')) {
            //create an image blob
            //send to s3
            $random = str_random(32);
            exec("convert -background lightblue -fill blue label:{$random} /tmp/temp.jpg");
            $s3 = new S3Client(['profile' => 'personal', 'version' => "latest", 'region' => 'us-east-1']);
            $body = file_get_contents("/tmp/temp.jpg");
            $uploader = new ObjectUploader($s3, "batch-example", "example-1/" . $random . ".jpg", $body, "public-read");

            $uploader->upload();

            $this->info("Uploaded $random.jpg");

            $count++;
        }
    }
}

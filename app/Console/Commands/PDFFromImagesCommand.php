<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PDFFromImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:orchestrate {--bucket-folder=false} {--jobs=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take a bunch of images and make a PDF';

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
        //For all the images in the bucket
        //Spin up children tasks
        //Then when those are done
        //See how large of a job and
        //  make new task with large memory
        //  or if small enough make the pdf here
        //Finally when done put result in Queue/Callback/Incomings for requester
    }
}

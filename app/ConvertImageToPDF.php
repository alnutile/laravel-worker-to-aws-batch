<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 9/3/17
 * Time: 1:37 PM
 */

namespace App;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use AlfredNutileInc\Incomings\Log;

use Illuminate\Http\File as StreamFile;

class ConvertImageToPDF
{


    private $url;

    protected $destination_path = "/tmp";

    protected $file_name;

    protected $file_extension;

    protected $random_folder_id;

    private $command;

    private $results = 0;

    private $output = [];
    private $remote_destination;

    public function handle($url, $destination = false)
    {

        $this->url = $url;

        $this->remote_destination = $destination;

        $this->getNameFromUrl();

        $this->downloadFile();

        $this->convertFile();


        if ($destination) {
            $this->savePDFToS3();
        }

        return true;
    }

    private function getNameFromUrl()
    {
        $parts = pathinfo($this->url);
        $this->file_name = $parts['filename'];
        $this->file_extension = $parts['extension'];
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * @return mixed
     */
    public function getFileExtension()
    {
        return $this->file_extension;
    }

    protected function downloadFile()
    {
        $image = $this->fileGetContents();
        $this->verifyDestinationIsReady();
        $destination = $this->destination_path . "/" .
            $this->getRandomFolderId() . "/" . $this->file_name . "." . $this->file_extension;
        File::put($destination, $image);

        if (!File::exists($destination)) {
            throw new \Exception(sprintf("Error saving file from %url", $this->url));
        }
    }

    /**
     * @return mixed
     */
    public function getRandomFolderId()
    {
        if (!$this->random_folder_id) {
            $this->setRandomFolderId();
        }
        return $this->random_folder_id;
    }

    /**
     * @param mixed $random_folder_id
     */
    public function setRandomFolderId($random_folder_id = null)
    {
        if ($random_folder_id === null) {
            $random_folder_id = str_random(32);
        }
        $this->random_folder_id = $random_folder_id;
    }

    private function verifyDestinationIsReady()
    {
        if (!File::exists($this->destination_path . "/" . $this->getRandomFolderId())) {
            File::makeDirectory($this->destination_path . "/" . $this->getRandomFolderId());
        }
    }

    /**
     * @return string
     */
    public function getDestinationPath()
    {
        return $this->destination_path;
    }

    /**
     * @param string $destination_path
     */
    public function setDestinationPath($destination_path)
    {
        $this->destination_path = $destination_path;
    }

    private function convertFile()
    {
        $this->command = sprintf(
            "convert %s %s",
            $this->getFullPathAndFileNameToSource(),
            $this->getFullPathAndFileNameToDestination()
        );

        $this->runConvert();
    }

    protected function getFullPathAndFileNameToSource()
    {
        return
            $this->getDestinationPath() . "/" .
            $this->getRandomFolderId() . "/" .
            $this->getFileName() . "." .
            $this->getFileExtension();
    }

    protected function getFullPathAndFileNameToDestination()
    {
        return
            $this->getDestinationPath() . "/" .
            $this->getRandomFolderId() . "/" .
            $this->getFileName() . ".pdf";
    }

    protected function runConvert()
    {
        $process = new Process($this->command);
        Log::info(sprintf("Running command %s", $this->command));
        $process->run();
        if (!$process->isSuccessful()) {
            $error = sprintf(
                'The command "%s" failed.'."\n\nExit Code: %s(%s)\n\nWorking directory: %s",
                $process->getCommandLine(),
                $process->getExitCode(),
                $process->getExitCodeText(),
                $process->getWorkingDirectory()
            );
            Log::debug($error);
            throw new ProcessFailedException($process);
        }

        $this->output = $process->getOutput();
    }

    protected function fileGetContents()
    {
        return file_get_contents($this->url);
    }

    private function savePDFToS3()
    {
        //comes in like `batch-example/JOB_ID`
        $destination_array = explode("/", $this->remote_destination);
        $bucket = $destination_array[0];
        $folder = implode("/", array_slice($destination_array, 1));

        $name_and_path_to_send_to = $folder . "/" . $this->getFileName() . ".pdf";

        $source_path_and_name =
            $this->getDestinationPath() . "/" .
            $this->getRandomFolderId() . "/" .
            $this->getFileName() . ".pdf";

        Log::info("Saving file to $folder, from $source_path_and_name and bucket $bucket");

        //help set bucket on fly
        $config = [
            'key' => env("AWS_ACCESS_KEY_ID"),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => 'us-east-1',
            'version' => 'latest',
            'bucket' => $bucket
        ];
        //not sure why this alone did not do it yet
        //Config::set("filesystem.disks.s3.bucket", $bucket);

        $results = Storage::createS3Driver($config)
            ->putFileAs(
                $folder,
                new StreamFile($source_path_and_name),
                $this->getFileName() . ".pdf",
                "public"
            );
        Log::info("Results from Putting file $results");
    }
}

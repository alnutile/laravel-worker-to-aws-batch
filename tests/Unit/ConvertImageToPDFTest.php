<?php

namespace Tests\Unit;

use App\ConvertImageToPDF;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

function file_get_contents()
{
    return "foo";
}

class ConvertImageToPDFTest extends TestCase
{

    public function testCanGetImageAndSave()
    {
        $url = "https://foo.com/foo.jpg";

        File::shouldReceive('put')->once()->andReturn(true);

        File::shouldReceive('exists')
            ->with("/tmp/55555555")->once()->andReturn(true);

        File::shouldReceive('exists')
            ->with("/tmp/55555555/foo.jpg")->once()->andReturn(true);

        $converter = \Mockery::mock(ConvertImageToPDF::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $converter->shouldReceive('runConvert')->andReturn(true);
        $converter->shouldReceive('fileGetContents')->andReturn("foo");

        $converter->setRandomFolderId(55555555);

        $converter->handle($url, false);
    }

    public function testCanConvertImageToPDF()
    {
        //url
        $url = "https://dl.dropboxusercontent.com/s/d2sx0wjheb7dk0p/example_batch.jpg";

        $image = File::get(base_path("tests/fixtures/example_batch.jpg"));
        $converter = \Mockery::mock(ConvertImageToPDF::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $converter->shouldReceive('fileGetContents')->andReturn($image);

        $converter->handle($url, false);

        $file_and_path = $this->getDestinationPath($converter) . "example_batch.pdf";
        $this->assertFileExists($file_and_path);
    }

    protected function getDestinationPath($converter)
    {
        return $converter->getDestinationPath() . "/" . $converter->getRandomFolderId() . "/";
    }

    /**
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function testFatalErrorGetFileNameFromURL()
    {
        $url = "https://dl.dropboxusercontent.com/s/d2sx0wjheb7dk0p/example_batch.jpg";

        $converter = \Mockery::mock(ConvertImageToPDF::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $converter->shouldReceive('downloadFile')->andReturn(true);


        $converter->handle($url, false);
    }

    public function testGetFileNameFromURL()
    {
        $url = "https://dl.dropboxusercontent.com/s/d2sx0wjheb7dk0p/example_batch.jpg";

        $converter = \Mockery::mock(ConvertImageToPDF::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $converter->shouldReceive('runConvert')->andReturn(true);

        $converter->handle($url, false);

        $this->assertEquals("example_batch", $converter->getFileName());
        $this->assertEquals("jpg", $converter->getFileExtension());
    }
}

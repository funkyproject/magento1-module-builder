<?php
namespace Adexos;

use Composer\Script\Event;
use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;
use JasonLewis\ResourceWatcher\Event as ResourceEvent;
use JasonLewis\ResourceWatcher\Resource\FileResource;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SymlinkScript
{
    public static function postUpdate(Event $event)
    {
        static::createSymlinks();
    }

    public static function watch()
    {
        $files = new IlluminateFilesystem;
        $tracker = new Tracker;

        $watcher = new Watcher($tracker, $files);

        $listener = $watcher->watch(__DIR__.'/../src/');

        $filesystem = new Filesystem();

        $listener->onAnything(function (ResourceEvent $resource, $path) {


            if ($path instanceof FileResource) {
                /* @var $path FileResource */

                $relativePath = str_replace([dirname(__DIR__).'/src/', $path->getSplFileInfo()->getFilename()], '', $path->getPath());
                $relativePathName = str_replace([dirname(__DIR__).'/src/'], '', $path->getSplFileInfo()->getPathname());
                $fileInfo = new SplFileInfo($path->getPath(), $relativePath , $relativePathName);

                if ($resource->getCode() === ResourceEvent::RESOURCE_DELETED) {
                    self::removeSymlink($fileInfo);
                } else {
                    self::createSymlink($fileInfo);
                }
            }
        });

        $watcher->start();
    }

    private static function createSymlinks()
    {
        $from = self::getFrom();
        $to = self::getTo();

        $finder = Finder::create()
            ->in($from)
            ->files();

        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($finder as $file)
        {
            self::createSymlink($file);
        }
    }

    /**
     * @return string
     */
    private static function getFrom()
    {
        $from = __DIR__ . '/../src/';

        return $from;
    }

    /**
     * @return string
     */
    private static function getTo()
    {
        $to = __DIR__ . '/../html/';

        return $to;
    }

    /**
     * @param $file
     * @param $from
     * @param $to
     * @param $filesystem
     */
    private static function createSymlink(SplFileInfo $file)
    {
        $filesystem = new Filesystem();
        $path = $file->getRelativePath();
        var_dump(self::getFrom() . $path );
        $origin = realpath(self::getFrom() . $path )  . $file->getFilename();
        $copy = self::getTo() . $path   .  $file->getFilename();
        $filesystem->symlink($origin, $copy, true);

        echo sprintf ('File sync : %s', $copy).PHP_EOL;
    }

    private static function removeSymlink(SplFileInfo $file)
    {
        $filesystem = new Filesystem();
        $path = $file->getRelativePath();
        $copy = realpath(self::getTo() . $path ) .  $file->getFilename();
        $filesystem->remove($copy);

        echo sprintf ('File removed : %s', $copy).PHP_EOL;
    }

}

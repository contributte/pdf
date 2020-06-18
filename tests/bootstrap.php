<?php

// The Nette Tester command-line runner can be
// invoked through the command: ../vendor/bin/tester .

if (@!include __DIR__ . '/../vendor/autoload.php') {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

// configure environment
Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

// create temporary directory
define('TEMP_DIR', __DIR__ . '/tmp/' . getenv(Tester\Environment::THREAD));
@mkdir(dirname(TEMP_DIR)); // @ - directory may already exist
Tester\Helpers::purge(TEMP_DIR);

function test(\Closure $function)
{
    $function();
}

class Notes
{

    static public $notes = array();

    public static function add($message)
    {
        self::$notes[] = $message;
    }

    public static function fetch()
    {
        $res = self::$notes;
        self::$notes = array();
        return $res;
    }

}

function getTempDir(): string
{
    $dir = __DIR__ . '/tmp/' . getenv(Tester\Environment::THREAD);

    if (empty($GLOBALS['\\lock'])) {
        // garbage collector
        $GLOBALS['\\lock'] = $lock = fopen($dir . '/lock', 'w');
        if (rand(0, 100)) {
            flock($lock, LOCK_SH);
            @mkdir(dirname($dir));
        } elseif (flock($lock, LOCK_EX)) {
            Tester\Helpers::purge(dirname($dir));
        }

        @mkdir($dir);
    }

    return $dir;
}

function createContainer($source, $config = null, $params = []): ?Nette\DI\Container
{
    $class = 'Container' . md5((string) lcg_value());
    if ($source instanceof Nette\DI\ContainerBuilder) {
        $source->complete();
        $code = (new Nette\DI\PhpGenerator($source))->generate($class);
    } elseif ($source instanceof Nette\DI\Compiler) {
        if (is_string($config)) {
            $loader = new Nette\DI\Config\Loader;
            $config = $loader->load(is_file($config) ? $config : Tester\FileMock::create($config, 'neon'));
        }
        $code = $source->addConfig((array) $config)
                ->setClassName($class)
                ->compile();
    } else {
        return null;
    }

    $file = getTempDir() . "/$class.php";
    file_put_contents($file, "<?php\n\n$code");
    require $file;
    return new $class($params);
}

<?php

$pluginName = "EasyEdit";
$outputFile = $pluginName . ".phar";

if (file_exists($outputFile)) {
    unlink($outputFile);
}

$phar = new Phar($outputFile);
$phar->setSignatureAlgorithm(Phar::SHA1);
$phar->startBuffering();

$include = ["src", "resources", "plugin.yml", "icon.png"];

foreach ($include as $item) {
    $path = __DIR__ . "/" . $item;
    if (!file_exists($path)) {
        continue;
    }
    if (is_file($path)) {
        $phar->addFile($path, $item);
    } else {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative = str_replace(__DIR__ . "/", "", $file->getPathname());
                $phar->addFile($file->getPathname(), $relative);
            }
        }
    }
}

$phar->setStub('<?php echo "PocketMine-MP plugin ' . $pluginName . '\n"; if(extension_loaded("phar")){ $phar = new \Phar(__FILE__); foreach($phar->getMetadata() as $key => $value){ echo "$key: $value\n"; } } __HALT_COMPILER();');
$phar->stopBuffering();

echo "Built $outputFile successfully\n";

<?php

$include = [
    'src',
];

$iterator = new AppendIterator();
foreach ($include as $path) {
    $iterator->append(new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    ));
}

#foreach ($iterator as $k => $v) {
#    print("$k\t\t$v\n");
#}

$phar = new \Phar(dirname(__FILE__).'/orm.phar', 0, 'orm.phar');
$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->startBuffering();
$phar->buildFromIterator($iterator, 'src');
$phar->setStub($phar->createDefaultStub('main.php'));
$phar->stopBuffering();
<?php

$path = dirname(\Phar::running(false));

if (strlen($path) > 0) {
    define('BASEDIR', \Phar::running());
} else {
    define('BASEDIR', dirname(__FILE__));
}

require_once BASEDIR."/Entity.php";
require_once BASEDIR."/AssociativeEntity.php";
require_once BASEDIR."/Id.php";
require_once BASEDIR."/ManyToMany.php";
require_once BASEDIR."/OneToMany.php";
require_once BASEDIR."/OneToOne.php";

require_once BASEDIR."/Compiler.php";
require_once BASEDIR."/SQLOutput.php";

fprintf(STDERR, "SQL Preprocessor\n");
fprintf(STDERR, "By Emerson C. Lima 22/Fev/2021\n");
fprintf(STDERR, "BASEDIR: %s\n", BASEDIR);

# parse args
$shortopts = "f::n::u::d::o::";
$longopts = ["file::namespace::user::driver::"];
$restind = null;
$opts = getopt($shortopts, $longopts, $restind);
$args = array_slice($argv, $restind);

if (isset($opts["file"])) $opts["f"] = $opts["file"];
if (isset($opts["namespace"])) $opts["n"] = $opts["namespace"];
if (isset($opts["user"])) $opts["u"] = $opts["user"];
if (isset($opts["driver"])) {
    $opts["d"] = $opts["driver"]; 
} else if (!isset($opts["d"])) {
    $opts["d"] = 'sqlite';
}

fprintf(STDERR, "==> Bootstraping classes...\n");
foreach ($args as $arg) {
    fprintf(STDERR, "including %s", $arg);
    require_once $arg;
    fprintf(STDERR, "\n");
}

fprintf(STDERR, "==> Compiling classes...\n");
$builder = new Compiler();
foreach (get_declared_classes() as $klass) {
    $r = new ReflectionClass($klass);    
    $fn = $r->getFileName();
    if ((isset($opts["u"]) || isset($opts["user"])) && !$r->isUserDefined()) continue;    
    if (isset($opts["f"]) && !fnmatch($opts["f"], $fn)) continue;    
    if (isset($opts["n"]) && $r->getNamespaceName() != $opts["n"]) continue;
        
    $attributes = array_merge(
        $r->getAttributes(Entity::class), 
        $r->getAttributes(AssociativeEntity::class)
    );
    
    if (empty($attributes)) continue;

    fprintf(STDERR, "$fn:$klass\n");
    
    $builder->compile($klass);    
}
fprintf(STDERR, "==> Output\n");
//$output = new DebugOutput(); 
$output = new SQLOutput($opts["d"]);
$r = yaml_parse_file(
print(yaml_emit($builder->output($output)));
print("\n");
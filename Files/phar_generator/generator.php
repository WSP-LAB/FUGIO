<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$output_path = __DIR__;
# CLASS DEFINE
class dummy_class_r353t{
    function __construct(){
        $this->used_methods = array();
    }
}


# Internal CLASS
$internal_phar = $output_path . "/exploit_internal.phar";
$phar = new Phar($internal_phar);
$phar->startBuffering();
$phar->setStub("<?php __HALT_COMPILER();");

$payload = new dummy_class_r353t();
$phar->setMetadata($payload);

$phar->addFromString("internal_dummy.txt", "DUMMY");
$phar->stopBuffering();
chmod($internal_phar, 0777);
$changing_internal_file = "dummy.txt";
$changing_internal_full_path = $output_path . "/" . $changing_internal_file;
rename($internal_phar, $changing_internal_full_path);

# Permission & External class
$external_phar = $output_path . "/dummy_class_r353t.phar";
$phar = new Phar($external_phar);
$phar->startBuffering();
$phar->setStub("<?php __HALT_COMPILER();");

$payload = new dummy_class_r353t();
$phar->setMetadata($payload);

$phar->addFile($changing_internal_file);
$phar->stopBuffering();

# Permission & Rename
chmod($external_phar, 0777);
$phar_validator = $output_path . "/dummy_class_r353t.png";
rename($external_phar, $phar_validator);

# Delete unnecessary file
unlink($changing_internal_full_path);

echo md5_file($phar_validator);
?>

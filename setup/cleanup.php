<?php
$root = dirname(dirname(__FILE__));
include_once 'test.php';
include_once $root . '/src/db.php';
// Create modules
$modules = $_POST['module'];
$tables = explode(',', $modules);
$module = explode(',', $modules);

// Create database tables
foreach ($tables as $table) {
  $table = trim($table);
  $table = strtolower($table);
  $table = str_replace(' ', '_', $table);
  $sql = "CREATE TABLE {$table} (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY) engine='InnoDB';";
  $db->query($sql);
}

function create_module($name){
  $root = dirname(dirname(__FILE__));
  $dir = $root . '/' . $name;
  if (!is_dir($dir)) {
    mkdir($dir);
  }
  touch($dir . '/form_fields.php');
  $files = ['index.php', 'new.php', 'edit.php', 'show.php', 'delete.php'];
  foreach ($files as $file) {
    $file = $dir . '/' . $file;
    if (!is_file($file)) {
      $file = fopen($file, 'w+');
      $text = "
      <?php
      require '../src/init.php';
      ";
      fwrite($file, $text);
      fclose($file);
    }
  }

  $class = dirname(dirname(__FILE__)) . '/src/classes/' . $name . '.class.php';
  if (!is_file($class)) {
    $table_name = $name;
    $name = ucfirst($name);
    $file = fopen($class, 'w+');
    $text = "
    <?php
    class {$name} extends A{
    static protected \$table_name = '{$table_name}';
    static protected \$db_columns = ['id',];

    public \$id;

    public function __construct(\$args = []){
    }
  } // End of class
  ";
  fwrite($file, $text);
  fclose($file);
  }
}

foreach ($module as $name) {
  $name = trim($name);
  $name = str_replace(' ', '_', $name);
  $name = strtolower($name);
  create_module($name);
} // End of module creation

$target = '../setup';
touch('../index.php');
$fh = fopen('../index.php', 'w+');
$body = 'Start working on your project, setup has been completed.<br/>This file should also be deleted.';
fwrite($fh, $body);
fclose($fh);
if(is_dir($target)){
  $files = scandir($target);
  foreach($files as $file){
  unlink($file);
}
rmdir($target);
}
rename('../simplesamlphp-1.19.6', '../simplesaml');
header('Location: ../index.php');
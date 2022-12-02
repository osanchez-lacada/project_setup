<?php
$db = $_POST['db'];
include_once './saml_loader.php';
$root = dirname(dirname(__FILE__));

// Database helper function
function read_file($file)
{
    touch('reader.php');
    $fh = fopen('reader.php', 'w+');
    $body = "<?php include_once '$file'; ?>";
    fwrite($fh, $body);
    fclose($fh);
    $fh = fopen('reader.php', 'r');
    $fh = fclose($fh);
    unlink('reader.php');
}

// Create base structure
$base = ['src', 'src/classes', 'src/cache', 'template'];
$files = ['azure_call.php', 'db.php', 'init.php', 'functions.php'];
foreach ($base as $dir) {
    if (!is_dir($root . '/' . $dir)) {
        mkdir($root . '/' . $dir);
    }
// Create src files
    if (is_dir($root . '/src')) {
        touch($root . '/src/scripts.js');
        touch($root . '/src/style.css');
        foreach ($files as $file) {
            $item = $root . '/src/' . $file;
            touch($item);
        }

        if (is_file($root . '/src/azure_call.php')) {
            $file = fopen($root . '/src/azure_call.php', 'w+');
            $body = "<?php
define('ROOT', dirname(dirname(__FILE__)));
require 'db.php';
require 'functions.php';
require ROOT.'/vendor/autoload.php';
require ROOT.'/src/MSGRAPH/functions.php';
require ROOT.'/src/quickcache.php';
foreach(glob(ROOT . '/src/classes/*.class.php') as \$file){
require_once(\$file);
}

function get_classes(\$class){
if(preg_match('/\A\w+\Z/', \$class)){
\$ignore = ['SimpleSAML', 'lib'];
if(!in_array(\$class)){
include(ROOT . '/src/classes/'.\$class.'.class.php');
}
}
}
spl_autoload_register('get_classes');

A::set_database(\$db);

\$accessToken = getAccessToken();
\$users = getUsers(\$accessToken);

\$old_list = dirname(__FILE__) . '/cache/old';
\$new_list = dirname(__FILE__) . '/cache/temp';

refresh_users(\$old_list, \$new_list);
?>";
            fwrite($file, $body);
            fclose($file);
        }

        if (is_file($root . '/src/db.php')) {
// Add credentials to db.php
            $file = fopen($root . '/src/db.php', 'w+');
            $host = trim($db[0]);
            $user = trim($db[1]);
            $pass = trim($db[2]);
            $dbase = trim($db[3]);
// Create temp file with DB info and remove it
            touch('test.php');
            $temp = fopen('test.php', 'w+');
            $text = "
<?php
\$db = new mysqli('{$host}','{$user}','{$pass}');
if(\$db){
\$sql = 'CREATE DATABASE IF NOT EXISTS $dbase;';
\$result = \$db->query(\$sql);
if(\$result != true){
echo mysqli_error(\$db);
exit();
}
}
";
            fwrite($temp, $text);
            fclose($temp);
// DB created temp file removed
            $body = "
<?php
define('HOST', '{$host}');
define('USER', '{$user}');
define('PASS', '{$pass}');
define('DB', '{$dbase}');
\$db = new mysqli(HOST, USER, PASS, DB);
?>
";
            fwrite($file, $body);
            fclose($file);
        }

        if (is_file($root . '/src/init.php')) {
// Build init.php
            $file = fopen($root . '/src/init.php', 'w+');
            $body = "
<?php
ob_start();
date_default_timezone_set('America/Los_Angeles');
define('ROOT', dirname(dirname(__FILE__)));
define('HEADER', ROOT . '/template/header.php');
define('JS', ROOT . '/src/scripts.js');
require ROOT . '/simplesaml/lib/_autoload.php';
require ROOT.'/vendor/autoload.php';
require ROOT.'/src/MSGRAPH/functions.php';
require ROOT.'/src/quickcache.php';
require 'db.php';
require 'functions.php';

//SSO
\$as = new \SimpleSAML\Auth\Simple('default-sp');
\$as->requireAuth();

\$attributes = \$as->getAttributes();

\$session = \SimpleSAML\Session::getSessionFromRequest();
\$session->cleanup();
//SSO end

\$today = date('Y-m-d');
\$now = date('Y-m-d H:i:s');

// App classes
foreach(glob(ROOT . '/src/classes/*.class.php') as \$file){
require_once(\$file);
}

function get_classes(\$class){
if(preg_match('/\A\w+\Z/', \$class)){
\$ignore = ['SimpleSAML', 'lib'];
if(!in_array(\$class, \$ignore)){
include(ROOT . '/src/classes/'.\$class.'.class.php');
}
}
}
spl_autoload_register('get_classes');

A::set_database(\$db);
include_once HEADER;
?>
";
            fwrite($file, $body);
            fclose($file);
        }

        if (is_file($root . '/src/functions.php')) {
            $file = fopen($root . '/src/functions.php', 'w+');
            $body = "
<?php
function u(\$string='') {
return urlencode(\$string);
}

function raw_u(\$string='') {
return rawurlencode(\$string);
}

function h(\$string='') {
return htmlspecialchars(\$string);
}

function error_404() {
header(\$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
exit();
}

function error_500() {
header(\$_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
exit();
}

function redirect_to(\$location) {
header('Location: ' . \$location);
exit;
}

function is_post_request() {
return \$_SERVER['REQUEST_METHOD'] == 'POST';
}

function is_get_request() {
return \$_SERVER['REQUEST_METHOD'] == 'GET';
}

function current_page(){
\$url = 'http://' . \$_SERVER['HTTP_HOST'] . \$_SERVER['REQUEST_URI'];
\$validURL = str_replace('&', '&amp;', \$url);
return str_replace('_', ' ', basename(\$validURL, '.php'));
}

if(!function_exists('money_format')) {
function money_format(\$format, \$number) {
return '\$' . number_format(\$number, 2);
}
}

function week_range(\$week, \$year) {
\$dto = new DateTime();
\$dto->setISODate(\$year, \$week);
\$ret['week_start'] = \$dto->format('Y-m-d');
\$dto->modify('+6 days');
\$ret['week_end'] = \$dto->format('Y-m-d');
return \$ret;
}

function readable_date(\$date){
if(is_null(\$date) || \$date == '' || \$date == '1969-12-31' || \$date == '0001-01-01' || \$date == '1000-01-01' || \$date == '1000-01-01'){
return '';
}else{
return date('m/d/Y', strtotime(\$date));
}
}

function ssn(\$ssn){
return substr(\$ssn, 0, 3) . '-' . substr(\$ssn, 3, 2) . '-' . substr(\$ssn, 5, 4);
}

// Validation Functions
function is_blank(\$value) {
return !isset(\$value) || trim(\$value) === '';
}

function has_presence(\$value) {
return !is_blank(\$value);
}

function has_length_greater_than(\$value, \$min) {
\$length = strlen(\$value);
return \$length > \$min;
}

function has_length_less_than(\$value, \$max) {
\$length = strlen(\$value);
return \$length < \$max;
}

function has_length_exactly(\$value, \$exact) {
\$length = strlen(\$value);
return \$length == \$exact;
}

function has_length(\$value, \$options) {
if(isset(\$options['min']) && !has_length_greater_than(\$value, \$options['min'] - 1)) {
return false;
} elseif(isset(\$options['max']) && !has_length_less_than(\$value, \$options['max'] + 1)) {
return false;
} elseif(isset(\$options['exact']) && !has_length_exactly(\$value, \$options['exact'])) {
return false;
} else {
return true;
}
}

function has_inclusion_of(\$value, \$set) {
return in_array(\$value, \$set);
}

function has_exclusion_of(\$value, \$set) {
return !in_array(\$value, \$set);
}

function has_string(\$value, \$required_string) {
return strpos(\$value, \$required_string) !== false;
}

function has_valid_email_format(\$value) {
\$email_regex = '/\A[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\Z/i';
return preg_match(\$email_regex, \$value) === 1;
}

function is_editor_different(\$editor, \$assigned){
if(\$editor == \$assigned){
return false;
}else{
return true;
}
}

// Status Error Functions
function require_login() {
global \$session;
global \$db;
if(!\$session->is_logged_in()) {
redirect_to('/');
} else {
\$path = ROOT . \$_SERVER['REQUEST_URI'];
\$sql = 'SELECT username FROM users WHERE id = \$user->id';
\$result = \$db->query(\$sql);
foreach(\$result as \$row){
\$expected = array_shift(\$row);
}

if(\$session->is_logged_in() && \$expected === \$session->username){
// Access the site
}else{
redirect_to('/');
}
}
}

function display_errors(\$errors=array()) {
\$output = '';
if(!empty(\$errors)) {
\$output .= '<div class=\"alert alert-danger mt-3 mb-3\">';
\$output .= 'Please fix the following errors:';
\$output .= '<ul>';
foreach(\$errors as \$error) {
\$output .= '<li>' . h(\$error) . '</li>';
}
\$output .= '</ul>';
\$output .= '</div>';
}
return \$output;
}

function display_session_message() {
global \$session;
\$msg = \$session->message();
if(isset(\$msg) && \$msg != '') {
\$session->clear_message();
return '<div id=\"message\">' . h(\$msg) . '</div>';
}
}

function days_apart(\$first, \$last){
\$diff = strtotime(\$first) - strtotime(\$last);
\$days = abs(\$diff / (60*60*24));
return \$days;
}

function completed_date(\$date){
\$result = date_create(\$date);
\$interval = '5 days';
\$new_date = date_sub(\$result, date_interval_create_from_date_string('\$interval'));
return \$new_date;
}

function future_date(\$days){
// Supply number of days to add to today
\$today = date('Y-m-d');
\$today = date_create(\$today);
\$interval = '\$days days';
\$new_date = date_add(\$today, date_interval_create_from_date_string('\$interval'));
return \$new_date->format('Y-m-d');
}

function decoded(\$file, \$target){
\$result = [];
foreach(\$file as \$row){
\$data = json_decode(\$row);
usort(\$data, function(\$a, \$b){
return \$a->displayName < \$b->displayName ? -1 : 1;
});
foreach(\$data as \$row){
if(!is_null(\$row->givenName) || !is_null(\$row->surname)){
if(array_key_exists('businessPhones', (array)\$row)){
unset(\$row->businessPhones);
}
array_push(\$result, \$row);
}
}
}
return \$result;
}

function flatten_array(\$array){
\$data = '';
foreach(\$array as \$row){
\$data .= \$row->id . ',';
}
\$data = substr_replace(\$data, '', -1);
return explode(',', \$data);
}

function find_removed(\$old, \$new){
\$list = array_diff(\$old, \$new);
return \$list;
}

function find_added(\$old, \$new){
\$list = array_diff(\$new, \$old);
return \$list;
}

function refresh_users(\$old, \$new){
\$old_list = file(\$old);
\$new_list = file(\$new);

\$old_array = decoded(\$old_list, 'givenName');
\$new_array = decoded(\$new_list, 'givenName');

\$old_flat  = flatten_array(\$old_array);
\$new_flat  = flatten_array(\$new_array);

\$removed = find_removed(\$old_flat, \$new_flat);
\$added = find_added(\$old_flat, \$new_flat);

User::remove_users(\$old_array, \$removed);
User::add_users(\$new_array, \$added);

//update file
\$oldFile = fopen(\$old,'w+');
fwrite(\$oldFile,file_get_contents(\$new));
fclose(\$oldFile);
}

function checkURL(){
\$from = \$_SERVER['HTTP_REFERER'] ?? NULL;
if(is_null(\$from)){
redirect_to('../index.php');
echo 'redirect to index';
}
}

function is_invalid(\$var){
if(\$var=='' || \$var==null){
return 1;
}else{
return 0;
}
}

?>
";
            fwrite($file, $body);
            fclose($file);
        }

// Create main class file
        if (is_dir($root . '/src/classes')) {
            touch($root . '/src/classes/a.class.php');
            if (is_file($root . '/src/classes/a.class.php')) {
                $file = fopen($root . '/src/classes/a.class.php', 'w+');
                $body = "
<?php
class A {
static protected \$db;
static protected \$table_name = '';
static protected \$columns = [];
public \$errors = [];

static public function set_database(\$db){
self::\$db = \$db;
}

static public function find_by_sql(\$sql){
\$result = self::\$db->query(\$sql);
if(!\$result){
exit('Database query failed on ' . static::\$table_name);
}

\$object_array = [];
while(\$record = \$result->fetch_assoc()){
\$object_array[] = static::instantiate(\$record);
}

\$result->free();
return \$object_array;
}

static public function find_all(){
\$sql = 'SELECT * FROM ' . static::\$table_name;
return static::find_by_sql(\$sql);
}

static public function count_all(){
\$sql = 'SELECT COUNT(*) FROM ' . static::\$table_name;
\$result_set = self::\$db->query(\$sql);
\$row = \$result_set->fetch_array();
return array_shift(\$row);
}

static public function find_by_id(\$id){
\$sql = 'SELECT * FROM ' . static::\$table_name . ' WHERE id = ' . self::\$db->escape_string(\$id);
\$object_array = static::find_by_sql(\$sql);
if(!empty(\$object_array)){
return array_shift(\$object_array);
}else{
return false;
}
}

static protected function instantiate(\$record){
\$object = new static;
foreach(\$record as \$property => \$value){
if(property_exists(\$object, \$property)){
\$object->\$property = \$value;
}
}
return \$object;
}

protected function validate(){

}

protected function create(){
\$this->validate();
if(!empty(\$this->errors)) { return false; }

\$attributes = \$this->sanitized_attributes();
\$sql = 'INSERT INTO ' . static::\$table_name . ' (';
\$sql .= join(', ', array_keys(\$attributes));
\$sql .= ' ) VALUES (\'';
\$sql .= join('\', \'', array_values(\$attributes));
\$sql .= '\')';
\$result = self::\$db->query(\$sql);

if(\$result){
\$this->id = self::\$db->insert_id;
}
return \$result;
}

protected function update(){
\$this->validate();
if(!empty(\$this->errors)) { return false; }

\$attributes = \$this->sanitized_attributes();
\$attribute_pairs = [];
foreach(\$attributes as \$key => \$value){
\$attribute_pairs[] = \$key . '=\'' . \$value . '\'';
}

\$sql = 'UPDATE ' . static::\$table_name . ' SET ';
\$sql .= join(', ', \$attribute_pairs);
\$sql .= ' WHERE id = \'' . self::\$db->escape_string(\$this->id) . '\' ';
\$sql .= 'LIMIT 1';
\$result = self::\$db->query(\$sql);
return \$result;
}

public function save(){
if(isset(\$this->id)){
return \$this->update();
}else{
return \$this->create();
}
}

public function merge_attributes(\$args=[]){
foreach(\$args as \$key => \$value){
if(property_exists(\$this, \$key) && !is_null(\$value)){
\$this->\$key = \$value;
}
}
}

public function attributes(){
\$attributes = [];
foreach(static::\$db_columns as \$column){
if(\$column == 'id') { continue; }
\$attributes[\$column] = \$this->\$column;
}
return \$attributes;
}

protected function sanitized_attributes(){
\$sanitized = [];
foreach(\$this->attributes() as \$key => \$value){
\$sanitized[\$key] = self::\$db->escape_string(\$value);
}
return \$sanitized;
}

public function delete(){
\$sql = 'UPDATE ' . static::\$table_name . ' SET to_delte = 1 WHERE id = ' . self::\$db->escape_string(\$this->id) . ' LIMIT 1';
\$result = self::\$db->query(\$sql);
return \$result;
}
}
?>
";
                fwrite($file, $body);
                fclose($file);
            }
        }
// Add cache files
        if (is_dir($root . '/src/cache')) {
            touch($root . '/src/cache/old');
            touch($root . '/src/cache/temp');
        } // End of cache folder

    } // End of source folder

// Add files to template folder
    if (is_dir($root . '/template')) {
        $files = ['footer.php', 'header.php', 'navigation.php'];
        foreach ($files as $file) {
            touch($root . '/template/' . $file);
        }
    } // End of template folder

} // End of base directory array

// create ignore file
touch($root.'/.gitignore');
$file = fopen($root.'/.gitignore', 'w+');
$body = 'src/db.php
src/init.php
src/cache/*
.htaccess
simplesaml/*
vendor/*
';
fwrite($file,$body);
fclose($file);

header('Location: modules.php');

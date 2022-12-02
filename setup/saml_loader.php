<?php
set_time_limit(0);
$source = "https://github.com/simplesamlphp/simplesamlphp/releases/download/v1.19.6/simplesamlphp-1.19.6.tar.gz";
$destination = "simplesaml.tar.gz";
$block = 4096;

$sh = fopen($source, "rb");
$dh = fopen($destination, "w");

while(!feof($sh)){
  if(fwrite($dh, fread($sh, $block)) === false){
    echo "FWRITE ERROR";
    flush();
  }
}

fclose($sh);
fclose($dh);

// extract contents
$load = new PharData('simplesaml.tar.gz');
$load->decompress();
$load = new PharData('simplesaml.tar');
$load->extractTo('../');

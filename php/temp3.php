<?php
  include "./include/PHPcom.php";

$com = new PHPcom( "/dev/ttyUSB0", 19200, 8, 1 );
$com->Setup( );

$com->Open( );

while(1){
	$temp = $com->read(200,"\n");
	if ($temp <> ""){
		echo( $temp."\n" );
	}
}


$com->Close( );
?>

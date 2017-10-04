<?php
include "PHPcom/PHPcom.php";

$com = new PHPcom( "/dev/serial/by-id/usb-FTDI_usb_temp_1_USBTEMP1-if00-port0", 19200, 8, 1 );
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

<?php
	
  if (empty($argv[1])) die("Brak nazwy urządzenia\n");

  $lista_urzadzen = glob("/dev/serial/by-id/*".$argv[1]."*");	
  $ilosc_urzadzen = count($lista_urzadzen);
  echo "Ilość zgodnych urządzeń: ".$ilosc_urzadzen."\n";
  
  if ($ilosc_urzadzen == 0) die("Brak podpiętych urządzeń\n");
  $karta_temp = "";
  
  foreach ($lista_urzadzen as $urzadzenie){	  
		$karta_temp = $urzadzenie;
	    continue;
  }
  
  include "PHPcom/PHPcom.php";


  $com = new PHPcom( $karta_temp, 19200, 8, 1 );
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

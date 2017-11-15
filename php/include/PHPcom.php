<?php

class PHPcom #PHPcom object
{
  public $Baud; #Baud rate
  public $DataBits; #Data bits
  public $StopBits; #Stop bits
  public $Port; #Path to device file eg. /dev/ttyUSB1

  public $PortHandle; #File descriptor

  public function __construct( $Port, $Baud, $DataBits, $StopBits ) #Class construcor
  {
    $this->Port = $Port;
    $this->Baud = $Baud;
    $this->DataBits = $DataBits;
    $this->StopBits = $StopBits;
  }

  public function Setup( ) #Setup port device file (takes really long time to execuse, use ONCE)
  {
    if ( !file_exists( $this->Port ) ) trigger_error( "Device file doesn't exist!", E_USER_ERROR );

    $Command = sprintf( "stty -F %s %d cs%d", $this->Port, $this->Baud, $this->DataBits );
    $Command .= " ignbrk -brkint -imaxbel -opost -onlcr -isig -icanon -iexten";
    $Command .= " -echo -echoe -echok -echoctl -echoke noflsh -ixon -crtscts"; #More and more options

    if ( $this->StopBits == 1 )
    {
      $Command .= " -cstopb";
    }
    else if ( $this->StopBits == 2 )
    {
      $Command .= " cstopb";
    }

    #echo $Command;
    exec( $Command ); #Execute command
  }

  public function Open( ) #Open serial port device file (takes medium amount of time [~30ms])
  {
    $this->PortHandle = fopen( $this->Port, "w+" );
    if ( !$this->PortHandle )
    {
      trigger_error( "Error with opening device!", E_USER_ERROR );
      die( );
    }
  }

  public function Close( ) #Close serial port device file (takes much time, do not use too often [200ms+])
  {
    fclose( $this->PortHandle );
  }

  public function Write( ) #Write data to serial port (doesn't take much time)
  {
    #Write( Data ) - write data and end with 0x00
    #Write( Data, EndChar ) - Write data and end with specified end character

    switch ( func_num_args( ) )
    {
      case 1: #No args, end with 0x00
        fwrite( $this->PortHandle, func_get_arg( 0 ) . 0x00 );
        break;

      case 2: #Enc char specified
        fwrite( $this->PortHandle, func_get_arg( 0 ) . func_get_arg( 1 ) );
        break;

      default:
        trigger_error( "Invalid argument count for PHPcom->Write( );", E_USER_ERROR );
        break;
    }
  }

  public function Read( ) #Read and return data from serial port (time depends on amount of data)
  {
    # Read( ) - read to 0x00
    # Read( Length ) - read specified number of characters or to 0x00
    # Read( Length, EndChar ) - read specified number of characters or to specified end character

    switch ( func_num_args( ) )
    {
      case 0: #No args, read to 0x00
        $Data = stream_get_line( $this->PortHandle, 0, 0x00 );
        break;

      case 1: #Length specified or read to 0x00
        $Data = stream_get_line( $this->PortHandle, func_get_arg( 0 ), 0x00 );
        break;

      case 2: #Length and end character specified
        $Data = stream_get_line( $this->PortHandle, func_get_arg( 0 ), func_get_arg( 1 ) );
        break;

      default:
        trigger_error( "Invalid argument count for PHPcom->Read( );", E_USER_ERROR );
        break;
    }
    return $Data;
  }


  public function ProcessCommandsArray( $Commands ) #Execute array of commands and return responses
  {
    #Input data format: {command1, command2, ...}
    #Alternative input data format {{commands1, flags1, flag2}, {commands2, flags3, flag4}, ...}
    #Output data format: {{command1, response1}, {command2, response2}, ...}

    #Possible flags:
      # - noread - Don't read input after sending command (default: false)
      # - wendchar - Character to end write with (default: 0x00)
      # - rendchar - Character to end read with (default: 0x00)
      # - rlength - Maximum amount of characters to read (default: 0)

    for ( $i = 0; $i < count( $Commands ); $i++ )
    {
      if ( !is_array( $Commands[$i] ) )
      {
        #If command is not contained in array create it (simple input)
        $Commands[$i] = array( $Commands[$i] );
        $this->Write( $Commands[$i][0] );
        $Commands[$i][1] = $this->Read( );
      }
      else
      {
        #Check for "wend" flag
        if ( !array_key_exists( "wendchar", $Commands[$i] ) ) $Commands[$i]["wendchar"] = 0x00;

        if ( !array_key_exists( "noread", $Commands[$i] ) ) $Commands[$i]["noread"] = 0;
        $this->Write( $Commands[$i][0], $Commands[$i]["wendchar"] );

        if ( $Commands[$i]["noread"] == 0 ) #If "noread" flag is enabled don not read input
        {
          #Check for "rlength" flag
          if ( !array_key_exists( "rlength", $Commands[$i] ) ) $Commands[$i]["rlength"] = 0;
          #Check for "rendchar" flag
          if ( !array_key_exists( "rendchar", $Commands[$i] ) ) $Commands[$i]["rendchar"] = 0x00;

          $Commands[$i][1] = $this->Read( );
        }
      }
    }
    return $Commands;
  }
}

?>

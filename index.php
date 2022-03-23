<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

<form role="form" method="POST"> 
          <h2 class="resultadosCoring">Consultar mi scoring</h2>
        <div class="container">
            <!-- <div class="row"> -->
              <div class="col-xs-12 col-sm-4">
                <div class="form-group">
                  <label for="numeroDNI">Número de documento *</label>
                  <input type="number" class="form-control" name="numeroDNI" />
                </div>
              </div>
              <div class=" col-xs-12">
              <button type="submit" class="btn btn-datos">Consultar</button>
              </div>
        </div>
</form>    
</body>
</html>

 

<?php

if (isset($_POST['numeroDNI'])){
call_ws_libre_deudaSS('getInfoPersona');

}


//Funciones


// De acuerdo al servicio consultado y la respuesta xml obtenida, parsea y estandariza la salida.
// Setea "success" , "data" y "errors" del objeto resultante.
function get_xml_response_wsSS($service,$xml) { 

  $resp = array();
  $resp['success'] = true;
  $resp['data'] = array();
  $resp['errors'] = array();

  switch ($service) {
    case 'getInfoPersona':
        //echo "<pre>";print_r($xml);die();
        $resp['data'] = $xml;
        $resp['success'] = true;
     break;

  }

  return $resp;
}

//Prepara el request y llama al servicio
function do_call_ws_libre_deudaSS($service, $data){
    
  $url = 'https://tcaba2-pre.dgai.com.ar/ScoringWS/ScoringWS?wsdl';
  $options = array('trace' => TRUE);
  
  $client = new SoapClient($url, $options);
  $client->__setLocation($url);
  

  $response_xml = $client->__doRequest($data,$url,$service,0);

  //Remplazo los caracteres del String para que quede en formato XML
  $response_xml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response_xml);
  //Lo transformo en XML para luego codificarlo en JSON
  $response_xml = @simplexml_load_string($response_xml);
  //Encodeo en JSON
  $json = json_encode($response_xml);
  //Finalmente lo paso a un array de PHP
  $responseArray = json_decode($json, true); //true para obtener array, false para obtener objeto.

  //Genero un array de las keys para ver si luego de parameters me trae resultados o un error.
  $arrKey = array_keys($responseArray["soapenvBody"]["getInfoPersonaResponse"]["return"]['parameters']);
  $arr = array();
  //Si no trajo resultados el index 0 es error.
  if ($arrKey[0] == 'error') {

    //Genero un array mas cortito con los datos que me interesan. (Error -> descripcion)
    $arr = $responseArray["soapenvBody"]["getInfoPersonaResponse"]["return"]["parameters"]["error"]["@attributes"];
    echo $arr["descripcion"];

  }
  if($arrKey[0] == 'persona') {

    //Genero un array mas cortito con los datos que me interesan. (NombreApellido y CantidadVeces que llego a 0)
    $arr = $responseArray["soapenvBody"]["getInfoPersonaResponse"]["return"]["parameters"]["persona"]["@attributes"];
    echo '<h1 style="text-align:center; display:block; padding-bottom:20px; margin-top:-5px"> Resultados </h1>
    <div class="well">
        <div> <p><span>Nombre y Apellido</span> </p>
            <h3> '. $arr["nombreApellido"] .'</h3>
        </div>
        <div class="row" style="margin-top:50px;margin-bottom:10px;">
            <div class="col-xs-6 col-md-6">
                <p><span>Saldo <br/><br></span></p>
            </div>
            <div class="col-xs-6 col-md-6">
                <p><span>Cantidad de veces que <br/>llegó a cero:</span></p>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-6 col-md-6">
                <h3>'. $arr["saldo"] .'</h3>
            </div>
            <div class="col-xs-6 col-md-6">
                <h3> '. $arr["cantidadVecesLlegoA0"] .'</h3><p></p>
            </div>
        </div>
    </div>
    <p  style="color:#404041 ; font-size:10pt;">Información suministrada por la DAI.</p>';

  }
 

  return get_xml_response_wsSS($service,$response_xml);

  }

//Llama al webservice, de la manera que corresponde para cada servicio.
// Parsea la respuesta y la retorna estandarizada
function call_ws_libre_deudaSS($service){
  
  $user = 'XSCORING01';
  $pass = 'TEST12345';
  $extendedData = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:scor="ScoringWS">
  <soapenv:Header/>
    <soapenv:Body>
      <scor:getInfoPersona soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
         <tipoDocumento xsi:type="xsd:string">DNI</tipoDocumento>
         <numeroDocumento xsi:type="xsd:string">23608042</numeroDocumento>
         <userName xsi:type="xsd:string">'.$user.'</userName>
         <userPass xsi:type="xsd:string">'.$pass.'</userPass>
      </scor:getInfoPersona>
    </soapenv:Body>
  </soapenv:Envelope>';

try {

  $output = do_call_ws_libre_deudaSS($service, $extendedData);
  return $output;
}
catch (Exception $e) {
  echo 'Excepcion:' . $e;
  $resp['errors'][] = 'La consulta de infracciones se encuentra fuera de servicio en este momento. Por favor, reintente más tarde. Disculpe las molestias.';
  $resp['success'] = false;
  return $resp;

}



}



?>
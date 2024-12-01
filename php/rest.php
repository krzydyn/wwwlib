<?php
require_once($config["lib"]."modules.php");

/**
 * $data = array("key"=>"value",....)
 */
function restApi($method, $url, $data) {
	$curl = curl_init();
	$method = strtoupper($method);
	switch ($method) {
        case "POST": //post data
			$json = json_encode($data);
            curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type: application/json',                    
              'Content-Length: ' . strlen($json)
               ));
            if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
            break;
        case "PUT": //put file
            curl_setopt($curl, CURLOPT_PUT, true);
			//curl_setopt($curl, CURLOPT_INFILE, $data["infile"]);
            if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
            break;
        case "HEAD":
        case "DELETE":
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
			break;
		default: //GET
			$method="GET";
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
    }

	//curl_setopt($curl, CURLOPT_HEADER, 1); //to include header in output
	logstr("REST ".$method." url=".$url);
	curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    curl_close($curl);
	//logstr("API r=".$result);
    return $result;
}
?>

<?php
function VRageRemote($host, $port, $secretKey, $route, $method, $messageCount, $text) {
	$routeCheck = explode("/", $route);
	if(empty($routeCheck[0])) {
		$routeShifted = substr($route, 1);
		$route = $routeShifted;
	}

	if($route == "v1/session/chat" && $method == "GET" && $messageCount != false) {
		$query = "?MessageCount=".$messageCount;
	} else {
		$query = "";
	}

	$prefix = "/vrageremote/".$route.$query;
	$url = "http://".$host.":".$port.$prefix;

	$date = date("r");
	$nonce = rand(0, getrandmax());

	$message = $prefix."\r\n".$nonce."\r\n".$date."\r\n";
	$messageBuffer = utf8_encode($message);
	$key = base64_decode($secretKey);
	$computedHash = hash_hmac("sha1", $messageBuffer, $key, true);
	$hash = base64_encode($computedHash);

	$header[0] = "Date: ".$date;
	$header[1] = "Authorization: ".$nonce.":".$hash;
	if($route == "v1/session/chat" && $method == "POST" && $text != false) {
		$textEncode = json_encode($text);
		$textLenght = strlen($textEncode);

		$header[2] = "Content-Type: application/json";
		$header[3] = "Content-Length: ".$textLenght;
	}

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	if($route == "v1/session/chat" && $method == "POST" && $text != false) {
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $textEncode);
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$requestReturn = curl_exec($curl);
	curl_close($curl);

	if($route == "v1/server/ping") {
		$check = json_decode($requestReturn, true);

		if(!empty($check["data"]["Result"]) && $check["data"]["Result"] == "Pong") {
			$data["status"] = 1;
		} else {
			$data["status"] = 0;
		}
	} else {
		$data = json_decode($requestReturn, true);

		if($route == "v1/session/chat" && $method == "GET") {
			$i = 0;

			foreach($data["data"]["Messages"] as $dataMessage) {
				$data["data"]["Messages"][$i]["Timestamp"] = floor($dataMessage["Timestamp"]/10000000)-62135596800;

				$i++;
			}
		}
	}

	return $data;
}
?>
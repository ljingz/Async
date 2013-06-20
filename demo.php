<?php
require("Async.class.php");

$async = Async::init("http");
for($i=0;$i<10;$i++){
	$async->post("test", "/", "name=".$i, function($error, $header, $content){
		if($error){
			echo $error->__toString();
		}else{
			echo date("\r\nH:i:s\r\n");
			//print_r($header);
			print_r($content);
		}
	});
}
Async::run();
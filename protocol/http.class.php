<?php
/**
 * 
 * 异步HTTP
 * @author liuj
 *
 */

class http {
	protected $extend = null;
	protected $address = "";
	protected $port = 80;
	protected $timeout = null;
	protected $callback = null;
	protected $socket = null;
	
	public function __construct($extend){
		$this->extend = $extend;
	}
	
	public function get($host, $request, $callback, $timeout=30){
		//header
		$header = sprintf("GET %s HTTP/1.1\r\n", $request);
		$header .= sprintf("Host: %s\r\n", $host);
		$header .= "Connection: close\r\n\r\n";
		$this->request($host, $header, $callback, $timeout);
	}
	
	public function post($host, $request, $body, $callback, $timeout=30){
		//body
		if(is_array($body)){
			$body = http_build_query($body);
		}
		//header
		$header = sprintf("POST %s HTTP/1.1\r\n", $request);
		$header .= sprintf("Host: %s\r\n", $host);
		$header .= sprintf("Content-Length: %s\r\n", strlen($body));
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "User-Agent: AsyncHTTP\r\n";
		$header .= "Connection: close\r\n\r\n";
		//data
		$data = $header.$body."\r\n\r\n";
		$this->request($host, $data, $callback, $timeout);
	}
	
	private function request($host, $data, $callback, $timeout=30){
		//解析IP地址和端口
		$host = explode(":", $host);
		$this->address = gethostbyname($host[0]);
		if(count($host)>1){
			$this->port = $host[1];
		}
		//超时时间
		$this->timeout = $timeout;
		//发起请求
		$socket_file = "socket/".$this->extend.".class.php";
		if(file_exists($socket_file)){
			require_once $socket_file;
			//发起socket请求
			$this->socket = new socket($this->address, $this->port, $this->timeout);
			$this->socket->send($data, function($error, $output) use ($callback){
				if($error){
					if(is_callable($callback)){
						$callback($error, null, null);
					}else{
						throw $error;
					}
				}
				//拆分header头和主体
				$output = preg_split("/\\r\\n\\r\\n/", $output, 2);
				$callback(null, $output[0], $output[1]);
			});
		}else{
			$error = new Exception("参数错误");
			if(is_callable($callback)){
				$callback($error, null, null);
			}else{
				throw $error;
			}
		}
	}
	
	public function run(){
		if($this->socket){
			$this->socket->run();
		}
	}
}
<?php
/**
 * 
 * 异步socket，使用libevent扩展
 * @author liuj
 *
 */

class socket {
	protected $address = "";
	protected $port = "";
	protected $timeout = null;
	protected static $base_event = null;
	
	public function __construct($address, $port, $timeout=30){
		if(!self::$base_event){
			self::$base_event = event_base_new();
		}
		$this->address = $address;
		$this->port = $port;
		$this->timeout = $timeout;
	}
	
	public function send($data, $callback=null){
		//创建socket连接
		$socket = stream_socket_client(sprintf("%s:%d", $this->address, $this->port), $errno, $errstr, $this->timeout, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT);
		//监听写事件
		$event = event_new();
		event_set($event, $socket, EV_WRITE, function($socket, $events, $arg) use ($data, $callback){
			fwrite($socket, $data, strlen($data));
			//监听读事件
			$event = event_new();
			event_set($event, $socket, EV_READ, function($socket, $events, $arg) use ($callback){
				$result = "";
				while(!feof($socket)){
					$result .= fread($socket, 1024);
				}
				fclose($socket);
				$callback(null, $result);
			}, array($event, self::$base_event));
			event_base_set($event, self::$base_event);
			event_add($event);
		}, array($event, self::$base_event));
		event_base_set($event, self::$base_event);
		event_add($event);
	}
	
	public function run(){
		event_base_loop(self::$base_event);
	}
}
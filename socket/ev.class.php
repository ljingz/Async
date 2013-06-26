<?php
/**
 * 
 * 异步socket，使用Ev扩展
 * @author liuj
 *
 */

class socket {
	protected $address = "";
	protected $port = "";
	protected $timeout = null;
	
	public function __construct($address, $port, $timeout=30){
		$this->address = $address;
		$this->port = $port;
		$this->timeout = $timeout;
	}
	
	public function send($data, $callback=null){
		//创建socket连接
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if(!$socket){
			$callback(new Exception(socket_strerror(socket_last_error($socket))), null);
			return;
		}
		//连接服务器
		$result = socket_connect($socket, $this->address, $this->port);
		if(!$result){
			$callback(new Exception(socket_strerror(socket_last_error($socket))), null);
			return;
		}
		socket_set_nonblock($socket);
		//监视写事件
		$write_watcher = new EvIo($socket, Ev::WRITE, function($watcher) use ($socket, $data, $callback){
			//停止write监视
			$watcher->stop();
			//发送数据
			$result = socket_write($socket, $data, strlen($data));
			if(!$result){
				$error = new Exception(socket_strerror(socket_last_error($socket)));
				socket_close($socket);
				$callback($error, null);
				return;
			}
			//监视读事件
			$read_watcher = new EvIo($socket, Ev::READ, function($watcher) use ($socket, $callback){
				//停止read监视
				$watcher->stop();
				//接收数据
				$result = "";
				while($output = socket_read($socket, 1024, PHP_NORMAL_READ)){
					$result .= $output;
				}
				if($result){
					if(is_callable($callback)){
						$callback(null, $result);
					}
				}else{
					$callback(new Exception(socket_strerror(socket_last_error($socket))), null);
				}
				//关闭socket连接
				socket_close($socket);
			});
			Ev::run();
		});
	}
	
	public function run(){
		Ev::run();
	}
}
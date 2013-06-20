<?php
/**
 * 
 * 异步IO
 * @author liuj
 *
 */

class Async {
	protected static $object = null;
	
	public static function init($type = "http", $extend = "ev"){
		//判断扩展是否可用
		if(!extension_loaded($extend)){
			throw new Exception($extend."扩展未开启");
		}
		//加载类文件
		$class_file = sprintf("protocol/%s.class.php", $type);
		if(file_exists($class_file)){
			require_once $class_file;
			self::$object = new $type($extend);
			return self::$object;
		}else{
			throw new Exception("参数错误");
		}
	}
	
	public static function run(){
		if(self::$object){
			self::$object->run();
		}
	}
}
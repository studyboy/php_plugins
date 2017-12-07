<?php
/**
 * 
 * +------------------------------------------------
 * 敏感詞過濾類
 * +------------------------------------------------
 * @author gaosongwang <songwanggao@gmail.com>
 * +-------------------------------------------------
 * @version 2016/6/3
 * +-------------------------------------------------
 */
namespace Addcn\Model\Tools\Filter;

use Addcn\Core\Library\Cache;

class TextFilter{
	
	public $path;
	private $sensitive = array();
	private $sensitiveDic = null;
//	private $readType = 'file';
	
	public function __construct($configPath){
		$this->path = $configPath;
	}
	
	public function read(){
		if(is_readable($this->path)){
			$this->sensitive = file_get_contents( 
						$this->path, 
						false,
			 			stream_context_create(array(
			 				'http' => array(
			 					'header' => array('timeout'=>20)
			 				),
			 				'https' => array(
			 					'header' => array('timeout' => 20)
			 				),
			 			))
			 		);
		}else{
			die('Dic file access deny!');
		}
		
	}
	/**
	 * 
	 * 敏感詞字典創建
	 */
	public function sensitiveWordDic(){
		
		$key = __CLASS__.":".__METHOD__.'filter';
		
		if( $sesitive = \cache::get($key) ) return $sesitive;
		
		$sesitive = $this->createSensitiveWordDic();
		
		//set cache
		\cache::set($key, $sesitive, 3600*3);
		
		return $sesitive;
	}
	public static function clearCache(){
		
		$key = __CLASS__.":".__METHOD__.'filter';
		
		return \cache::delete($key);
	}
	/**
	 * 
	 * 文本搜索
	 * @param unknown_type $content
	 * @param unknown_type $encoding
	 */
	public function sensitiveWordSearch($content, $encoding = 'utf-8'){

		$conlen = mb_strlen($content, $encoding);

		$sessitiveWord = new \ArrayObject(array());
		
		//get dic config;
		$hashMap = $this->sensitiveWordDic();
		
		for($j=0; $j < $conlen; $j++){
		
			$word = strtolower(mb_substr($content, $j, 1, $encoding));
			
			//escape word;
			if(empty($word) || $this->isEscapeWord($word, $encoding)){
				continue;
			}
	
			$nowNode = $hashMap->offsetGet($word);
		
			if( !$nowNode instanceof  \ArrayObject ){
				continue;
			} 

			$end = $j+1;

			while ($end < $conlen){
				
				$newword = strtolower(mb_substr($content, $end, 1, $encoding));
				
				//escape new word;
				if(empty($newword) || $this->isEscapeWord($newword, $encoding)){
					$end++;
					continue;
				}
				
				if( $nowNode->offsetGet('isEnd') == 1){
					
					$sword = mb_substr($content,$j,$end-$j, $encoding);
					$swordNum = $sessitiveWord->offsetGet($sword);
					
					$sessitiveWord->offsetSet($sword, ($swordNum ? $swordNum : 0)+1);
				}

				$nextNode = $nowNode->offsetGet($newword);

				if(!$nextNode instanceof \ArrayObject) break;
				
				$nowNode = $nextNode;
				
				$end++;
			}
			
		}
		
		return $sessitiveWord->getArrayCopy();
		
	}
	
	/**
	 * 
	 * 字典創建
	 * @param unknown_type $encoding
	 * @throws \RuntimeException
	 */
	public function createSensitiveWordDic($encoding = 'utf-8'){
		
		$this->read();
		
		if( empty($this->sensitive) ) throw new \RuntimeException('敏感詞表為空！');

		$hashMap = new \ArrayObject(array());
		
		$strArr  = explode(',', $this->sensitive);

		foreach ($strArr as $k=>$v){
			
			$v   = trim($v);
			
			$len = mb_strlen($v,$encoding);
			
			$nowMap = $hashMap;
		
			for ($i=0; $i<$len; $i++){

				$word = mb_substr(trim($v), $i, 1, $encoding); 
				
				if( empty($word) || $this->isEscapeWord($word) ) continue;
				
		      	if( !$nowMap instanceof \ArrayObject) continue;
				
		      	$wordMap = $nowMap->offsetGet($word);
		
				if($wordMap){
					$nowMap = $wordMap;
				}else{
					$newMap = new \ArrayObject(array());
					$newMap -> offsetSet('isEnd',0);
					$nowMap->offsetSet(strtolower($word), $newMap);
			   		$nowMap = $newMap;
				}
				
				if($i == $len-1){
					$nowMap->offsetSet('isEnd', 1);
				}
			}
		
		}
		
		return $hashMap;
	}
	public function escapeWord($content , $encoding = 'utf-8'){
		
		$conlen = mb_strlen($content, $encoding);
		$n = '';
		for($j=0; $j < $conlen; $j++){
			$word = mb_substr($content, $j, 1, $encoding);
			
			if( empty($word) || $this->isEscapeWord($word, $encoding)) continue;
			
			$n .= $word;
			
		}
		return $n;
	}
	/**
	 * 
	 * 特殊文本過濾
	 * @param unknown_type $word
	 * @param unknown_type $encoding
	 * return boolean
	 * a-z ={97,122},
	 * A-Z ={65,90}
	 * 1-9 ={49,57}
	 * @ = {64}
	 * word ={19968,40869}
	 */
	public function isEscapeWord($word, $encoding = 'utf-8'){
		
		$unicode = Unicode::charCodeAt($word, $encoding);
		
		switch (true){
			case $unicode >= 97 && $unicode <= 122 :
				
				return false;
			break;
			case $unicode >=65 && $unicode <= 90 :
				
				return false;
			break;
			case $unicode >=19968 && $unicode <=40869 :
				
				return false;
			break;
			case $unicode == 64:
				return false;
			break;
				
		}
		
		return true;
	}
}
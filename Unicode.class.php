<?php
namespace Addcn\Model\Tools\Filter;
/**
 * 
 * +------------------------------------------------
 * unicode 變化轉換類
 * +------------------------------------------------
 * @author gaosongwang <songwanggao@gmail.com>
 * +-------------------------------------------------
 * @version 2016/6/2
 * +-------------------------------------------------
 */

class Unicode {
	
	/**
	 * 
	 * 將字符轉為unicode碼
	 * @param unknown_type $str
	 * @param unknown_type $encoding
	 */
	public static function charCodeAt($str, $encoding  = false ){
		
		$encoding = $encoding ? $encoding : 'utf-8';
		
		if(strlen($str) == 1) return ord($str);
		
		$str = mb_substr($str, 0,1, $encoding);
		
		$convert = mb_convert_encoding($str, 'UCS-4BE', $encoding);
		
		$tmp = unpack('N', $convert);

		return $tmp[1];
	}
	/**
	 * 
	 * 將unicode碼轉為字符
	 * @param unknown_type $code
	 * @param unknown_type $encoding
	 */
	public static function fromCharCodeAt($code, $encoding = false){
		
		$encoding = $encoding ? $encoding : 'utf-8';
		
		if($code < 128) return chr($code);
		
		$tmp = pack('N', $code);
		
		$convert = mb_convert_encoding($tmp, $encoding, 'UCS-4BE');
		
		return $convert;
	}

}
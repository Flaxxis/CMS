<?php
class ActiveRecord extends Orm_ActiveRecord
{
	static public $_key = 'Flaxxis|Pass000';

	protected $db_sufix = '';

	public function getTable($new_table = '')
	{
		return $this->db_sufix . $this->_table;
	}
	
	static public function encryptMessage($message)
	{
		if(!$message)	return '';
		
		$cipher = mcrypt_module_open(MCRYPT_3DES,'','ecb','');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($cipher), MCRYPT_RAND);		
		mcrypt_generic_init($cipher, self::$_key, $iv);
		$encrypted = mcrypt_generic($cipher,$message);
		mcrypt_generic_deinit($cipher);	
		mcrypt_module_close($cipher);
		return base64_url_encode($encrypted);
	}
	
	static public function decryptMessage($message)
	{
		if(!$message)	return '';
		
		$cipher = mcrypt_module_open(MCRYPT_3DES,'','ecb','');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($cipher), MCRYPT_RAND);		
		mcrypt_generic_init($cipher, self::$_key, $iv);
		if($s = base64_url_decode($message))
			$decrypted = mdecrypt_generic($cipher, $s);
		else
			return '';
		mcrypt_generic_deinit($cipher);
		mcrypt_module_close($cipher);
		$decrypted = rtrim($decrypted, "\0");
		return $decrypted;
	}
	
	static function generateHash($in, $to_num = false, $pad_up = false, $passKey = 'Flaxxis-pass')
    {
        $index = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        //$index = "abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHJKLMNOPQRSTUVWXYZ";
        if ($passKey !== null) {
            // Although this function's purpose is to just make the
            // ID short - and not so much secure,
            // with this patch by Simon Franz (http://blog.snaky.org/)
            // you can optionally supply a password to make it harder
            // to calculate the corresponding numeric ID

            for ($n = 0; $n<strlen($index); $n++) {
                $i[] = substr( $index,$n ,1);
            }

            $passhash = hash('sha256',$passKey);
            $passhash = (strlen($passhash) < strlen($index))
            ? hash('sha512',$passKey)
            : $passhash;

            for ($n=0; $n < strlen($index); $n++) {
                $p[] =  substr($passhash, $n ,1);
            }

            array_multisort($p,  SORT_DESC, $i);
            $index = implode($i);
        }

        $base  = strlen($index);

        if ($to_num) {
            // Digital number  <<--  alphabet letter code
            $in  = strrev($in);
            $out = 0;
            $len = strlen($in) - 1;
            for ($t = 0; $t <= $len; $t++) {
                $bcpow = bcpow($base, $len - $t);
                $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
            }

            if (is_numeric($pad_up)) {
                $pad_up--;
                if ($pad_up > 0) {
                    $out -= pow($base, $pad_up);
                }
            }
            $out = sprintf('%F', $out);
            $out = substr($out, 0, strpos($out, '.'));
        } else {
            // Digital number  -->>  alphabet letter code
            if (is_numeric($pad_up)) {
                $pad_up--;
                if ($pad_up > 0) {
                    $in += pow($base, $pad_up);
                }
            }

            $out = "";
            for ($t = floor(log($in, $base)); $t >= 0; $t--) {
                $bcp = bcpow($base, $t);
                $a   = floor($in / $bcp) % $base;
                $out = $out . substr($index, $a, 1);
                $in  = $in - ($a * $bcp);
            }
            $out = strrev($out); // reverse
        }

        return $out;
    }

	protected function _generateUrl($shortcut) {
		$shortcut = $this->_translit(iconv('UTF-8','windows-1251',$shortcut));
		return str_replace('--', '-', preg_replace('/[^a-zA-Z0-9_-]+/','-', trim(strtolower($shortcut))));
	}
	
	public function _translit($str) {
		$transchars =array (
		"E1"=>"A",
		"E2"=>"B",
		"F7"=>"V",
		"E7"=>"G",
		"E4"=>"D",
		"E5"=>"E",
		"B3"=>"Jo",
		"F6"=>"Zh",
		"FA"=>"Z",
		"E9"=>"I",
		"EA"=>"I",
		"EB"=>"K",
		"EC"=>"L",
		"ED"=>"M",
		"EE"=>"N",
		"EF"=>"O",
		"F0"=>"P",
		"F2"=>"R",
		"F3"=>"S",
		"F4"=>"T",
		"F5"=>"U",
		"E6"=>"F",
		"E8"=>"H",
		"E3"=>"C",
		"FE"=>"Ch",
		"FB"=>"Sh",
		"FD"=>"W",
		"FF"=>"X",
		"F9"=>"Y",
		"F8"=>"Q",
		"FC"=>"Eh",
		"E0"=>"Ju",
		"F1"=>"Ja",

		"C1"=>"a",
		"C2"=>"b",
		"D7"=>"v",
		"C7"=>"g",
		"C4"=>"d",
		"C5"=>"e",
		"A3"=>"jo",
		"D6"=>"zh",
		"DA"=>"z",
		"C9"=>"i",
		"CA"=>"i",
		"CB"=>"k",
		"CC"=>"l",
		"CD"=>"m",
		"CE"=>"n",
		"CF"=>"o",
		"D0"=>"p",
		"D2"=>"r",
		"D3"=>"s",
		"D4"=>"t",
		"D5"=>"u",
		"C6"=>"f",
		"C8"=>"h",
		"C3"=>"c",
		"DE"=>"ch",
		"DB"=>"sh",
		"DD"=>"w",
		"DF"=>"x",
		"D9"=>"y",
		"D8"=>"",
		"DC"=>"eh",
		"C0"=>"ju",
		"D1"=>"ja",
		);

		/*
		$str = html_entity_decode($str);
		$str = preg_replace("!<script[^>]{0,}>.*</script>!Uis", "", $str);
		$str = strip_tags($str);
		$str = preg_replace("![^�������������������������������������Ũ��������������������������a-z0-9 ]!i", " ", $str);
		$str = preg_replace("![\s]{2,}!", " ", $str);*/
		$str = trim($str);
		$ns = convert_cyr_string($str, "w", "k");
		$b = '';
		for ($i=0;$i<strlen($ns);$i++)
		{
			$c=substr($ns,$i,1);
			$a=strtoupper(dechex(ord($c)));
			if (isset($transchars[$a])) {
				$a=$transchars[$a];
			} else if (ctype_alnum($c)){
				$a=$c;
			} else if (ctype_space($c)){
				$a='-';
			} else {
				$a='-';
			}
			$b.=$a;
		}
		return $b;
	}

	static public function HashVisit(){
		$sessionNamespace = new Zend_Session_Namespace('Visit');
		return $sessionNamespace->VisitHash;
	}
	
}
?>

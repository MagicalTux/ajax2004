<?php

function JSaddslashes($str) {
	if (is_null($str)) return 'null';
	if (is_int($str)) return $str;
	if (is_bool($str)) return ($str?'1':'0');
	if (is_array($str)) {
		$res='[';
		foreach($str as $val) {
			$res.=($res=='['?'':',').JSaddslashes($val);
		}
		$res.=']';
		return $res;
	}
	$res='';
	$str=iconv('utf-8', 'UNICODELITTLE', $str);
//	$str=substr($str, 2); // strip unicode header
	while(strlen($str)>0) {
		$char=substr($str, 0, 2);
		$str=substr($str, 2);
		if ($char{1}=="\0") {
			if ((ord($char{0})>=32) && (ord($char{0})<=127)) {
				switch($char{0}) {
					case '"': case '\\':
						$res.='\\';
						break;
					#
				}
				$res.=$char{0};
			} else {
				$res.='\\x'.bin2hex($char{0});
			}
		} else {
			$res.='\\u'.bin2hex($char{1}.$char{0});
		}
	}

	return '"'.$res.'"';
}

function ParseJS($txt) {
	$p=0;
	$max=strlen($txt);
	$result=null;
	$pointer=array(&$result);
	$level=0;
	$instring=0;
	while($p<$max) {
		$char = $txt{$p};
		if ($instring) {
			if ($char=='\\') {
				$p++;
				$char = $txt{$p};
				switch($char) {
					case 'n': $char="\n\x00"; break;
					case 'r': $char="\r\x00"; break;
					case 't': $char="\t\x00"; break;
					case 'x': $char=pack('H2', $txt{$p+1}.$txt{$p+2})."\x00"; $p+=2; break;
					case 'u': $char=pack('H4', $txt{$p+3}.$txt{$p+4}.$txt{$p+1}.$txt{$p+2}); $p+=4; break;
					case '"': $char.="\0"; break;
					default: $char=$char."\0"; break;
				}
				$pointer[$level].=$char;
				$p++;
				continue;
			}
			if ($char=='"') {
				// StringEnd
				$pointer[$level] = iconv('unicodelittle', 'utf-8', substr($pointer[$level], 2));
				$level--; $p++; $instring=0;
				continue;
			}
			$pointer[$level].=$char."\0";
			$p++;
		} else {
			if (($char==' ') || ($char==',')) {
				$p++;
				continue;
			}
			if (strtolower(substr($txt, $p, 4)) == 'null') {
				// NULL value
				if (is_null($pointer[$level])) {
					$pointer[$level]=NULL;
				} else {
					if (!is_array($pointer[$level])) die('Bad NULL definition exception !');
					$pointer[$level][]=NULL;
				}
				$p+=4;
				continue;
			}
			if ($char=='[') {
				// ArrayStart
				if (is_null($pointer[$level])) {
					$pointer[$level]=array();
				} else {
					if (!is_array($pointer[$level])) die('Bad ArrayStart definition exception !');
					$tmp=&$pointer[$level][];
					$tmp=array();
					$pointer[++$level]=&$tmp;
				}
				$p++;
				continue;
			}
			if ($char==']') {
				// ArrayEnd
				$level--; $p++;
				if ($level<-1) die('Bad ArrayEnd definition exception !');
				continue;
			}
			if ($char=='"') {
				// StringStart
				$instring=1;
				if (is_null($pointer[$level])) {
					$pointer[$level]="\xff\xfe";
				} else {
					if (!is_array($pointer[$level])) die('Bad StringStart definition exception !');
					$tmp=&$pointer[$level][];
					$tmp="\xff\xfe";
					$pointer[++$level]=&$tmp;
				}
				$p++;
				continue;
			}
			die('Bad character '.$char.' in definition '.$txt.' exception !');
		}
	}
	if ($level>0) die('Incomplete definition exception !');
	return $result;
}

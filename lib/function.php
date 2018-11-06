<?php


/**
* $str Unicode编码后的字符串
* $decoding 原始字符串的编码，默认utf-8
* $prefix 编码字符串的前缀，默认"&#"
* $postfix 编码字符串的后缀，默认";"
*/
function unicode_decode($unistr, $encoding = 'utf-8', $prefix = '&#', $postfix = ';')
{	
	$orig_str= $unistr;
	$arruni = explode($prefix, $unistr);
	$unistr = '';
	for ($i = 1, $len = count($arruni); $i < $len; $i++)
	{
		if (strlen($postfix) > 0) {
			$arruni[$i] = substr($arruni[$i], 0, strlen($arruni[$i]) - strlen($postfix));
		}
		$temp = intval($arruni[$i]);
		$unistr .= ($temp < 256) ? chr(0) . chr($temp) : chr($temp / 256) . chr($temp % 256);
	}
	$str = str_split(iconv('UCS-2', $encoding, $unistr));

	foreach ($str as $v)
	{
		$orig_str = preg_replace('/&#[\S]+?;/', $v, $orig_str,1);
	}
	return $orig_str;
}


// 去除iphone,ios,emoji表情
function removeEmoji($text)
{
    $clean_text = "";
    // Match Emoticons
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    $clean_text     = preg_replace($regexEmoticons, '', $text);
    // Match Miscellaneous Symbols and Pictographs
    $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
    $clean_text   = preg_replace($regexSymbols, '', $clean_text);
    // Match Transport And Map Symbols
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    $clean_text     = preg_replace($regexTransport, '', $clean_text);
    // Match Miscellaneous Symbols
    $regexMisc  = '/[\x{2600}-\x{26FF}]/u';
    $clean_text = preg_replace($regexMisc, '', $clean_text);
    // Match Dingbats
    $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
    $clean_text    = preg_replace($regexDingbats, '', $clean_text);
    return $clean_text;
}

// 过滤掉emoji表情
function filterEmoji($str)
{
    $str = preg_replace_callback(
        '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);

    return $str;
}


/**
 * 手动文本日志
 * @author xu
 * @copyright 2018-07-30
 */
function logdebug($text,$step) {
    file_put_contents(APP_DOWN.$step.'-'.date('YmdH',time()).'.log', date('Y-m-d H:i:s').'  '.$text."\n", FILE_APPEND);
}
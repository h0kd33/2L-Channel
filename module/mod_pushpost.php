<?php
class mod_pushpost{
	var $mypage;

	function mod_pushpost(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // 向系統登記模組專屬獨立頁面
		$this->mypage = $PMS->getModulePageURL(__CLASS__);
		$this->PUSHPOST_SEPARATOR = '[MOD_PUSHPOST_USEpw5287]';
		$this->PUSHPOST_DEF = 10; // 討論串最多顯示之推文筆數 (超過則自動隱藏，全部隱藏(包括動態功能)：0)
		$this->PUSH_EMOTIONS = array("","(ﾟ∀ﾟ)","(´∀`)","(*´∀`)","(*ﾟ∇ﾟ)","(*´д`)","(*ﾟーﾟ)","(ﾟ3ﾟ)","(´ー`)","(・_ゝ・)","(´,_ゝ`)","(´_ゝ`)","(・ー・)","(・∀・)","(ゝ∀･)",
			"(〃∀〃)","(*ﾟ∀ﾟ*)","(ﾟ∀。)","(`д´)","(`ε´)","(`ヮ´)","σ`∀´)","ﾟ∀ﾟ)σ","ﾟ∀ﾟ)ノ","(╬ﾟдﾟ)","(|||ﾟдﾟ)","(´ﾟдﾟ`)","(ﾟдﾟ)","Σ(ﾟдﾟ)","(;ﾟдﾟ)","(;´д`)",
			"(д)ﾟﾟ","(☉д⊙)","(((ﾟдﾟ)))","(`・´)","(´д`)","(-д-)","(&gt;д&lt;)","(つд⊂)","･ﾟ(ﾉд`ﾟ)","(TдT)","(*´ω`*)","(・ω・)","(｀･ω･)","(´・ω)","(｀・ω)",
			"(´・ω・`)","(`・ω・´)","(・_っ・)","(^_っ^)","(`_っ´)","(`ー´)","(´_っ`)","(´ρ`)","(ﾟωﾟ)","(oﾟωﾟo)","(^ω^)","(･ิ㉨･ิ)","(｡◕∀◕｡)","ヾ(´ε`ヾ)","(ノ・ω・)ノ",
			"(ノﾟ∀ﾟ)ノ","(σﾟдﾟ)σ","(σﾟ∀ﾟ)σ","|д`)","|ー`)","|дﾟ)","|∀ﾟ)","ヽ(●´∀`●)ﾉ","(ﾟдﾟ≡ﾟдﾟ)","。･ﾟ･(つд`ﾟ)･ﾟ･","_,._","ﾟÅﾟ)","⊂彡☆))д`)","⊂彡☆))д´)",
			"⊂彡☆))∀`)","(´∀((☆ミつ"); // 表情
		$this->GESTURES = array("","＜","｡o０","ﾉｼ","彡","o彡ﾟ","ゝ","ㄏ","ノ","ノ゛","ﾉ","ﾉミ","ツ","っ","っ゛","つ","つ゛","ｏ","凸","／","y","y━･~","y-～","≡3","～♪","♬","❤",
			"~♥","σ","ﾉ彡┴─┴","⌒＊","⌒☆","⌒★","≡☆"); //發言

		AttachLanguage(array($this, '_loadLanguage')); // 載入語言檔
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_pushpost : 文章推文機制';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '4th.Release.5-dev (v100804)';
	}

	/* 生成識別ID */
	function _getID(){
		return substr(crypt(md5(getREMOTE_ADDR().IDSEED.gmdate('Ymd', time() + TIME_ZONE * 3600)), 'id'), -8);
	}

	/* 記錄pushpost */
	function w_pustpost(&$postno, &$postname, &$postcom, &$posttime, &$postip){
		global $PMS;
		$PMS->loadModules('mod_audit');
		if($mod_audit=$PMS->getModuleInstance('mod_audit')) $mod_audit->w_pushpost($postno, $postname, $postcom, $posttime, $postip);
	}

	function autoHookHead(&$txt, $isReply){
		global $language;
		$txt .= '<style type="text/css">.pushpost { background-color: #fff; font-size: 0.8em; padding: 10px; }</style>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js"></script>
<script type="text/javascript">
// <![CDATA[
jQuery(function($){
	$(\'div.threadpost, div.reply\').find(\'div[@id^=openpush] > a\').bind(\'click\', function(){
		$(this).parent().parent().find(\'*[@id^=openpush]\')
			.filter(\'div[@id^=openpushbuttom]\').slideToggle().end()
			.filter(\'div[@id^=openpushpush]\').slideToggle();
		return false;
	});	

	$(\'div.threadpost, div.reply\').find(\'div[@id^=closepush] > a\').bind(\'click\', function(){
		$(this).parent().parent().parent().find(\'*[@id^=openpush]\')
			.filter(\'div[@id^=openpushpush]\').slideToggle().end()
			.filter(\'div[@id^=openpushbuttom]\').slideToggle();
		return false;
	});
});
// ]]>
</script>
<script type="text/javascript">
// <![CDATA[
var lastpushpost=0;
function mod_pushpostShow(pid){
	$g("mod_pushpostID").value = pid;
	$g("mod_pushpostName").value = getCookie("namec");
	$("div#mod_pushpostBOX").insertBefore($("div#r"+pid+">.quote"));
	if(lastpushpost!=pid) {
		$("div#mod_pushpostBOX").show();
	} else {
		$("div#mod_pushpostBOX").toggle();
	}
	lastpushpost = pid;
	return false;
}
function mod_pushpostSend(o3){
	var o0 = $g("mod_pushpostID"), o1 = $g("mod_pushpostName"), o2 = $g("mod_pushpostComm"), pp = $("div#r"+o0.value+">.quote"), o4 = $("#push_emotion option:selected").val(), o5 = $("#gesture option:selected").val();
	if(o2.value===""){ alert("'._T('modpushpost_nocomment').'"); return false; }
	o3.disabled = true;
	$.ajax({
		url: "'.str_replace('&amp;', '&', $this->mypage).'&no="+o0.value,
		type: "POST",
		data: {ajaxmode: true, name: o1.value, push_emotion: o4, gesture: o5, comm: o2.value},
		success: function(rv){
			if(rv.substr(0, 4)!=="+OK "){ alert(rv); o3.disabled = false; return false; }
			rv = rv.substr(4);
			(pp.find(".pushpost").length===0)
				? pp.append("<div class=\'pushpost\'>"+rv+"</div>")
				: pp.children(".pushpost").append("<br />"+rv);
			o0.value = o1.value = o2.value = o4 = o5 =""; o3.disabled = false;
			$("div#mod_pushpostBOX").hide();
		},
		error: function(){ alert("Network error."); o3.disabled = false; }
	});
}
// ]]>
</script>';
	}

	function autoHookFoot(&$foot){
		global $language;
		$ecnt=count($this->PUSH_EMOTIONS);
		$gcnt=count($this->GESTURES);
		for($i=0;$i<$ecnt;$i++) {
			$emot.="<option>".$this->PUSH_EMOTIONS[$i]."</option>\n";
		}		
		for($i=0;$i<$gcnt;$i++) {
			$gest.="<option>".$this->GESTURES[$i]."</option>\n";
		}
		$foot .= '
<div id="mod_pushpostBOX" style="display:none">
<input type="hidden" id="mod_pushpostID" />'._T('modpushpost_pushpost').' <ul>
<li>'._T('form_name').' <input type="text" id="mod_pushpostName" maxlength="20" /></li>
<li>'._T('form_comment').
'<select id="push_emotion" style="text-align:right;line-height:1.7em" class="push_area">'.$emot.'</select>'.
'<select id="gesture" class="push_area" style="line-height:1.7em">'.$gest.'</select>'.
'<input type="text" id="mod_pushpostComm" size="50" maxlength="50" /><input type="button" value="'._T('form_submit_btn').'" onclick="mod_pushpostSend(this)" /></li></ul>
</div>
';
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		global $language, $PIO;
		$pushcount = '';
		if($post['status'] != ''){
			$f = $PIO->getPostStatus($post['status']);
			$pushcount = $f->value('mppCnt'); // 被推次數
		}
		$arrLabels['{$QUOTEBTN}'] .= '&nbsp;<a href="'.$this->mypage.'&amp;no='.$post['no'].'" onclick="return mod_pushpostShow('.$post['no'].')">'.$pushcount._T('modpushpost_pushbutton').'</a>';
		if(strpos($arrLabels['{$COM}'], $this->PUSHPOST_SEPARATOR.'<br />') !== false){
			if($isReply || $pushcount <= $this->PUSHPOST_DEF) { // 回應模式

				$arrLabels['{$COM}'] = str_replace($this->PUSHPOST_SEPARATOR.'<br />', '<div class="pushpost">', $arrLabels['{$COM}']).'</div>';

			}else { // 頁面瀏覽

				$delimiter = strpos($arrLabels['{$COM}'], $this->PUSHPOST_SEPARATOR.'<br />'); // 定位符號位置
				$pushs = '';
				$pushcount = $pushcount - $this->PUSHPOST_DEF; //被隱藏的推文數目
				$push_array = explode('<br />', substr($arrLabels['{$COM}'], $delimiter + strlen($this->PUSHPOST_SEPARATOR.'<br />')));
				if($this->PUSHPOST_DEF > 0) {
					$pushs .= '
						<div class="pushpost">
							<div class="hide" id="openpushpush'.$arrLabels['{$NO}'].'">
								<div id="closepushmsg'.$arrLabels['{$NO}'].'">[<a href="#">'._T('modpushpost_close').'</a>]</div>'.implode('<br />', array_slice($push_array, 0, $pushcount)).'</div>

							<div id="openpushbuttom'.$arrLabels['{$NO}'].'"><a href="#">'._T('modpushpost_omitted').$pushcount._T('modpushpost_omitted2').'</a>
							<br />……
							</div>
							'.implode('<br />', array_slice($push_array, 0 - $this->PUSHPOST_DEF)).'';
				}else {//隱藏所有推文的話..
					$arrLabels['{$WARN_BEKILL}'] .='<span class="warn_txt2">'._T('modpushpost_omitted').$pushcount._T('modpushpost_omitted2').'</span>'."\n";
				}
				if($post['resto'] !== 0) $pushs .='</div>';
				$arrLabels['{$COM}'] = substr($arrLabels['{$COM}'], 0, $delimiter).$pushs;
			}
		}
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, &$upfileInfo, $accessInfo, $isReply){
		if(adminAuthenticate('check')) return; // 登入權限允許標籤留存不轉換 (後端登入修改文章後推文仍有效)
		if(strpos($com, $this->PUSHPOST_SEPARATOR."\r\n") !== false){ // 防止不正常的插入標籤形式
			$com = str_replace($this->PUSHPOST_SEPARATOR."\r\n", "\r\n", $com);
		}
	}


	function ModulePage(){
		global $PIO, $PTE, $language, $BAD_STRING;
		if(!isset($_GET['no'])) die('[Error] not enough parameter.');
		if(!isset($_POST['comm'])){
			$post = $PIO->fetchPosts($_GET['no']);
			if(!count($post)) die('[Error] Post does not exist.');

			$dat = $PTE->ParseBlock('HEADER', array('{$TITLE}'=>TITLE, '{$RESTO}'=>''));
			$dat .= '</head><body id="main">';
			$dat .= '<form action="'.$this->mypage.'&amp;no='.$_GET['no'].'" method="post">
'._T('modpushpost_pushpost').' <ul>
<li>'._T('form_name').' <input type="text" name="name" maxlength="20" /></li>
<li>'._T('form_comment').'<input type="text" name="comm" size="50" maxlength="50" /><input type="submit" value="'._T('form_submit_btn').'" /></li></ul>
</form>';
			echo $dat, '</body></html>';
		}else{
			if($_SERVER['REQUEST_METHOD'] != 'POST') die(_T('regist_notpost')); // 傳送方法不正確

			// 查IP
			$baninfo = '';
			$ip = getREMOTE_ADDR(); $host = gethostbyaddr($ip);
			if(BanIPHostDNSBLCheck($ip, $host, $baninfo)) die(_T('regist_ipfiltered', $baninfo));

			$name = CleanStr($_POST['name']); $comm = CleanStr($_POST['comm']);$push_emotion = CleanStr($_POST['push_emotion']);$gesture = CleanStr($_POST['gesture']);
			if(strlen($name) > 30) die(_T('modpushpost_maxlength')); // 名稱太長
			if(strlen($comm) > 160) die(_T('modpushpost_maxlength')); // 太多字
			if(strlen($comm) == 0) die(_T('modpushpost_nocomment')); // 沒打字
			$name = str_replace(array(_T('trip_pre'), _T('admin'), _T('deletor')), array(_T('trip_pre_fake'), '"'._T('admin').'"', '"'._T('deletor').'"'), $name);
			$pushID = $this->_getID();
			$pushtime = gmdate('y/m/d H:i:s', time() + intval(TIME_ZONE) * 3600);
			if(preg_match('/(.*?)[#＃](.*)/u', $name, $regs)){
				$cap = strtr($regs[2], array('&amp;'=>'&'));
				$salt = strtr(preg_replace('/[^\.-z]/', '.', substr($cap.'H.', 1, 2)), ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');
				$name = $regs[1]._T('trip_pre').substr(crypt($cap, $salt), -10);
			}
			if(!$name || ereg("^[ |　|]*$", $name)){
				if(ALLOW_NONAME) $name = DEFAULT_NONAME;
				else die(_T('regist_withoutname')); // 不接受匿名
			}

			$pushpost = '<font color="#117743">'.$name.'</font> : '.$push_emotion.$gesture.$comm.'<font color="#CC1105">('.$pushID.'</font>)('.$pushtime.')'; // 推文主體

			// 封鎖：限制出現之文字
			foreach($BAD_STRING as $value){
				if(strpos($name, $value)!==false || strpos($comm, $value)!==false){
			die(_T('regist_wordfiltered'));
				}
			}

			$post = $PIO->fetchPosts($_GET['no']);
			if(!count($post)) die('[Error] Post does not exist.'); // 被推之文章不存在
			$parentNo = $post[0]['resto'] ? $post[0]['resto'] : $post[0]['no'];
			$threads = array_flip($PIO->fetchThreadList());
			$threadPage = floor($threads[$parentNo] / PAGE_DEF);

			$p = ($parentNo==$post[0]['no']) ? $post : $PIO->fetchPosts($parentNo); // 取出首篇
			$flgh = $PIO->getPostStatus($p[0]['status']);
			if($flgh->exists('TS')) die('[Error] '._T('regist_threadlocked')); // 首篇禁止回應/同時表示禁止推文

			$post[0]['com'] .= ((strpos($post[0]['com'], $this->PUSHPOST_SEPARATOR)===false) ? '<br />'.$this->PUSHPOST_SEPARATOR : '').'<br />'.$pushpost;
			$flgh2 = $PIO->getPostStatus($post[0]['status']);
			$flgh2->plus('mppCnt'); // 推文次數+1
			$PIO->updatePost($_GET['no'], array('com'=>$post[0]['com'], 'status'=>$flgh2->toString())); // 更新推文
			$PIO->dbCommit();
			$this->w_pustpost($_GET['no'], $name, $comm, $pushtime, getREMOTE_ADDR());// 記錄
			if(STATIC_HTML_UNTIL == -1 || $threadPage <= STATIC_HTML_UNTIL) updatelog(0, $threadPage, true); // 僅更新討論串出現那頁
			deleteCache(array($parentNo)); // 刪除討論串舊快取

			if(isset($_POST['ajaxmode'])){
				echo '+OK ', $pushpost;
			}else{
				header('HTTP/1.1 302 Moved Temporarily');
				header('Location: '.fullURL().PHP_SELF2.'?'.time());
			}
		}
	}

	function _loadLanguage(){
		global $language;
		if(PIXMICAT_LANGUAGE != 'zh_TW' && PIXMICAT_LANGUAGE != 'ja_JP' && PIXMICAT_LANGUAGE != 'en_US') $lang = 'en_US';
		else $lang = PIXMICAT_LANGUAGE;

		if($lang=='zh_TW'){
			$language['modpushpost_nocomment'] = '請輸入內文';
			$language['modpushpost_pushpost'] = '[推文]';
			$language['modpushpost_pushbutton'] = '推';
			$language['modpushpost_maxlength'] = '你話太多了';
			$language['modpushpost_omitted'] = '推文 ';
			$language['modpushpost_omitted2'] = ' 篇被省略。';
			$language['modpushpost_close'] = '隱藏';
		}elseif($lang=='ja_JP'){
			$language['modpushpost_nocomment'] = '何か書いて下さい';
			$language['modpushpost_pushpost'] = '[推文]';
			$language['modpushpost_pushbutton'] = '推';
			$language['modpushpost_maxlength'] = 'コメントが長すぎます';
			$language['modpushpost_omitted'] = '推文 ';
			$language['modpushpost_omitted2'] = ' 部省略され。';
			$language['modpushpost_close'] = '隠し';

		}elseif($lang=='en_US'){
			$language['modpushpost_nocomment'] = 'Please type your comment.';
			$language['modpushpost_pushpost'] = '[Push this post]';
			$language['modpushpost_pushbutton'] = 'PUSH';
			$language['modpushpost_maxlength'] = 'You typed too many words';
			$language['modpushpost_omitted'] = 'Pushpost ';
			$language['modpushpost_omitted2'] = 'have be omitted.';
			$language['modpushpost_close'] = 'Display';
		}
	}
}
?>
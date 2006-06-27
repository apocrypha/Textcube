<?
define('ROOT', '../../..');
require ROOT . '/lib/includeForOwner.php';
require ROOT . '/lib/piece/owner/header3.php';
require ROOT . '/lib/piece/owner/contentMenu30.php';
$skins = array();
$dirHandler = dir(ROOT . "/skin");
while ($file = $dirHandler->read()) {
	if ($file == '.' || $file == '..')
		continue;
	if (!file_exists(ROOT . "/skin/$file/skin.html"))
		continue;
	$preview = "";
	if (file_exists(ROOT . "/skin/$file/preview.jpg"))
		$preview = "{$service['path']}/skin/$file/preview.jpg";
	if (file_exists(ROOT . "/skin/$file/preview.gif"))
		$preview = "{$service['path']}/skin/$file/preview.gif";
	array_push($skins, array('name' => $file, 'path' => ROOT . "/skin/$file/", 'preview' => $preview));
}

function writeValue($value, $label) {
?>
										<tr>
											<td class="name"><?=$label?></td>
											<td class="explain"><?=nl2br(addLinkSense($value, ' onclick="window.open(this.href); return false;"'))?></td>
										</tr>
<?
}
?>
						<script type="text/javascript">
							//<![CDATA[
								var isSkinModified = <?=($skinSetting['skin'] == "customize/$owner") ? 'true' : 'false'?>;
								
								function selectSkin(name) {
									if(isSkinModified) {
										if(!confirm("<?=_t('수정된 스킨을 사용중입니다. 새로운 스킨을 선택하면 수정된 스킨의 내용은 모두 지워집니다.\n스킨을 적용하시겠습니까?')?>"))
											return;
									}
									try {
										var request = new HTTPRequest("POST", "<?=$blogURL?>/owner/skin/change/");
										request.onSuccess = function() {
											isSkinModified = false;
											PM.showMessage("<?=_t('성공적으로 변경했습니다.')?>", "center", "bottom");
											document.getElementById('currentPreview').innerHTML = document.getElementById('preview_'+name).innerHTML;
											document.getElementById('currentInfo').innerHTML = document.getElementById('info_'+name).innerHTML;
											document.getElementById('currentButton').innerHTML = document.getElementById('button_'+name).innerHTML;
											window.location.href = "#currentSkinAnchor";
											//eleganceScroll('currentSkin',8);
											/*
											document.getElementById('currentSkinPreview').innerHTML = document.getElementById('preivew_'+name).innerHTML
											document.getElementById('currentSkinInfo').innerHTML = document.getElementById('info_'+name).innerHTML
											*/
										}
										request.onError = function() {
											alert(result['msg']);
										}
										request.send("skinName=" + encodeURIComponent(name));
									} catch(e) {
										alert(e.message);
									}
								}
							//]]>
						</script>
						
						<div id="part-skin-current" class="part">
							<h2 class="caption"><span class="main-text"><?=_t('현재 사용중인 스킨입니다')?></span></h2>
							
							<div class="data-inbox">
								<div id="currentSkin" class="section">
									<a id="currentSkinAnchor"></a>
									<div id="currentPreview" class="preview">
<?
if (file_exists(ROOT."/skin/".$skinSetting['skin']."/preview.jpg")) {
?>
										<img src="<?=$service['path']?>/skin/<?=$skinSetting['skin']?>/preview.jpg" alt="<?=_t('스킨 미리보기')?>" />
<?
} else if (file_exists(ROOT."/skin/".$skinSetting['skin']."/preview.gif")) {
?>
										<img src="<?=$service['path']?>/skin/<?=$skinSetting['skin']?>/preview.gif" alt="<?=_t('스킨 미리보기')?>" />
<?
} else {
?>
										<img src="<?=$service['path'].$service['adminSkin']?>/image/noPreview.gif" alt="<?=_t('스킨 미리보기')?>" />
<?
}
?>
									</div>
									<div class="information">
<?
if (file_exists(ROOT . "/skin/{$skinSetting['skin']}/index.xml")) {
?>
										<div id="currentInfo">
											<table cellspacing="0" cellpadding="0">
<?
	$xml = file_get_contents(ROOT . "/skin/{$skinSetting['skin']}/index.xml");
	$xmls = new XMLStruct();
	$xmls->open($xml, $service['encoding']);
	writeValue('<span class="skin-name">' . $xmls->getValue('/skin/information/name') . '</span> <span class="version">ver.' . $xmls->getValue('/skin/information/version') . '</span>', _t('제목'));
	writeValue($xmls->getValue('/skin/information/license'), _t('저작권'));
	writeValue($xmls->getValue('/skin/author/name'), _t('만든이'));
	writeValue($xmls->getValue('/skin/author/homepage'), _t('홈페이지'));
	writeValue($xmls->getValue('/skin/author/email'), _t('e-mail'));
	writeValue($xmls->getValue('/skin/information/description'), _t('설명'));
?>
											</table>
										</div>
										<div class="button-box">
											<span id="currentButton">
												<a class="preview-button button" href="<?=$blogURL?>/owner/skin/preview/?skin=<?=$skinSetting['skin']?>" onclick="window.open(this.href); return false;"><span class="text"><?=_t('미리보기')?></span></a>
												<span class="hidden">|</span>
												<a class="apply-button button" href="<?=$blogURL?>/owner/skin/change/?javascript=disabled&amp;skinName=<?=urlencode($skinSetting['skin'])?>" onclick="selectSkin('<?=$skinSetting['skin']?>'); return false;"><span class="text"><?=_t('적용')?></span></a>
											</span>
											<span class="hidden">|</span>
											<a class="edit-button button" href="<?=$blogURL?>/owner/skin/edit" onclick="window.open(this.href); return false;"><span class="text"><?=_t('편집하기')?></span></a>
										</div>
<?
} else {
?>
										<div id="currentInfo">
											<div id="customizedTable">
												<?=_t('수정된 스킨입니다.').CRLF?>
											</div>
										</div>
										<div class="button-box">
											<span id="currentButton"></span>
											<span class="hidden">|</span>
											<a class="edit-button button" href="<?=$blogURL?>/owner/skin/edit" onclick="window.open(this.href); return false;"><span class="text"><?=_t('편집하기')?></span></a>
										</div>
<?
}
?>
									</div>
								</div>
							</div>
						</div>
						
						<div id="currentSkinLoading" class="system-message" style="display: none;">
							<?=_t('로딩 중...')?>
						</div>
						
						<hr class="hidden" />
						
						<div id="part-skin-list" class="part">
							<h2 class="caption"><span class="main-text"><?=_t('사용가능한 스킨 목록입니다')?></span></h2>
							
							<div class="main-explain-box">
								<p class="explain"><?php echo _t('원하시는 스킨의 적용 버튼을 클릭하십시오.')?></p>
							</div>
							
							<div class="data-inbox">
<?
$count = 0;
for ($i = 0; $i < count($skins); $i++) {
	$skin = $skins[$i];
?>
								<div class="section">
									<div id="preview_<?=$skin['name']?>" class="preview">
<?
	if ($skin['preview'] == '') {
?>
										<img src="<?=$service['path'].$service['adminSkin']?>/image/noPreview.gif" alt="<?=_t('스킨 미리보기')?>" />
<?
	} else {
?>
										<img src="<?=$skin['preview']?>" alt="<?=_t('스킨 미리보기')?>" />
<?
	}
?>
									</div>
									<div class="information">
										<div id="info_<?=$skin['name']?>">
											<table cellspacing="0" cellpadding="0">
<?
	if (file_exists(ROOT . "/skin/{$skin['name']}/index.xml")) {
		$xml = file_get_contents(ROOT . "/skin/{$skin['name']}/index.xml");
		$xmls = new XMLStruct();
		$xmls->open($xml, $service['encoding']);
		writeValue('<span class="skin-name">' . $xmls->getValue('/skin/information/name') . '</span> <span class="version">ver.' . $xmls->getValue('/skin/information/version') . '</span>', _t('제목'));
		writeValue($xmls->getValue('/skin/information/license'), _t('저작권'));
		writeValue($xmls->getValue('/skin/author/name'), _t('만든이'));
		writeValue($xmls->getValue('/skin/author/homepage'), _t('홈페이지'));
		writeValue($xmls->getValue('/skin/author/email'), _t('e-mail'));
		writeValue($xmls->getValue('/skin/information/description'), _t('설명'));
	} else {
		writeValue($skin['name'], _t('제목'));
	}
?>
											</table>
										</div>
										<div id="button_<?=$skin['name']?>" class="button-box">
											<a class="preview-button button" href="<?=$blogURL?>/owner/skin/preview/?skin=<?=$skin['name']?>" onclick="window.open(this.href); return false;"><span><?=_t('미리보기')?></span></a>
											<span class="hidden">|</span>
											<a class="apply-button button" href="<?=$blogURL?>/owner/skin/change/?javascript=disabled&amp;skinName=<?=urlencode($skin['name'])?>" onclick="selectSkin('<?=$skin['name']?>'); return false;"><span><?=_t('적용')?></span></a>
										</div>
									</div>
								</div>
<?
}
?>
							</div>
						</div>
								
						<div id="part-skin-more" class="part">
							<h2 class="caption"><span class="main-text"><?=_t('스킨을 구하려면')?></span></h2>
							
							<div class="main-explain-box">
								<p class="explain"><?php echo _t('추가 스킨은 <a href="http://www.tattertools.com/skin" onclick="window.open(this.href); return false;" title="태터툴즈 홈페이지에 개설되어 있는 스킨 업로드 게시판으로 연결합니다.">태터툴즈 홈의 스킨 게시판</a>에서 구하실 수 있습니다. 일반적으로 스킨 파일을 태터툴즈의 skin 디렉토리로 업로드하면 설치가 완료됩니다. 업로드가 완료된 스킨은 이 메뉴에서 \'적용\' 버튼을 클릭하여 사용을 시작합니다.')?></p>
							</div>
						</div>
<?
require ROOT . '/lib/piece/owner/footer1.php';
?>
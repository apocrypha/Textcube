<?
define('__TATTERTOOLS_MOBILE__', true);
define('ROOT', '../../..');
require ROOT . '/lib/include.php';
list($entries, $paging) = getEntryWithPaging($owner, $suri['id']);
$entry = $entries ? $entries[0] : null;
printMobileHtmlHeader();
?>
<div id="content">
<?
printMobileTrackbackView($entry['id']);
?>
</div>
<?
printMobileNavigation($entry, true, false);
printMobileHtmlFooter();
?>
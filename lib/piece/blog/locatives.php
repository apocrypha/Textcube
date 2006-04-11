<?
$locativeView = '';
$prevPath = array();
foreach ($locatives as $locative) {
	$path = explode('/', $locative['location']);
	array_shift($path);
	for ($depth = $i = 0; $i < count($path); $i++, $depth += 20) {
		$spotView = $skin->locativeSpot;
		dress('local_spot_depth', $depth, $spotView);
		dress('local_spot', htmlspecialchars($path[$i]), $spotView);
		if (empty($prevPath[$i]) || ($path[$i] != $prevPath[$i]))
			$locativeView .= $spotView;
	}
	$entryView = $skin->locativeEntry;
	dress('local_info_depth', $depth + 20, $entryView);
	dress('local_info_link', "$blogURL/" . ($blog['useSlogan'] ? "entry/{$locative['slogan']}" : $locative['id']), $entryView);
	dress('local_info_title', htmlspecialchars($locative['title']), $entryView);
	$locativeView .= $entryView;
	$prevPath = $path;
}
dress('local', str_replace('[##_local_spot_rep_##]', $locativeView, $skin->locative), $view);
?>
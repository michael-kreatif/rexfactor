<?php

use rexfactor\DiffHtml;
use rexfactor\RexFactor;
use rexfactor\SkipList;
use rexfactor\TargetVersion;
use rexfactor\ViewHelpers;

$addon = rex_get('addon', 'string');

$addonLabel = ViewHelpers::getAddonLabel($addon);

$setList = rex_get('set-list', 'string');
$outputFormat = rex_get('format', 'string', DiffHtml::FORMAT_LINE_BY_LINE);
$targetVersion = rex_get('target-version', 'string', TargetVersion::PHP7_2_COMPAT);

$urlSkipped = rex_get('skip', 'array[string]', []);
$skipList = SkipList::fromStrings($urlSkipped);

if ($addon === '') {
    throw new rex_exception('Missing addon parameter');
}

$backUrl           = rex_url::backendPage('rexfactor/use-case').'&addon='.rex_escape($addon, 'url');
$formatToggleUrl   = rex_url::backendPage('rexfactor/preview').'&addon='.rex_escape($addon, 'url') .'&set-list='.rex_escape($setList, 'url');
$versionToggleUrl  = rex_url::backendPage('rexfactor/preview').'&addon='.rex_escape($addon, 'url') .'&set-list='.rex_escape($setList, 'url');
$skipUrl           = rex_url::backendPage('rexfactor/preview').'&addon='.rex_escape($addon, 'url') .'&set-list='.rex_escape($setList, 'url');
$applyUrl          = rex_url::backendPage('rexfactor/apply').'&addon='.rex_escape($addon, 'url') .'&set-list='.rex_escape($setList, 'url');

if ($outputFormat === DiffHtml::FORMAT_LINE_BY_LINE) {
    $formatToggleUrl .= '&format='.DiffHtml::FORMAT_SIDE_BY_SIDE;
    $formatToggleLabel = 'side-by-side';
} else {
    $outputFormat = DiffHtml::FORMAT_SIDE_BY_SIDE;
    $formatToggleUrl .= '&format='.DiffHtml::FORMAT_LINE_BY_LINE;
    $formatToggleLabel = 'line-by-line';
}

if ($targetVersion === TargetVersion::PHP8_1) {
    $versionToggleUrl .= '&target-version='.rex_escape(TargetVersion::PHP7_2_COMPAT, 'url');
    $versionToggleLabel = TargetVersion::PHP7_2_COMPAT;
} else {
    $versionToggleUrl .= '&target-version='.rex_escape(TargetVersion::PHP8_1, 'url');
    $versionToggleLabel = TargetVersion::PHP8_1;
    $targetVersion = TargetVersion::PHP7_2_COMPAT;
}

// append other configs, so we don't loose config state
$formatToggleUrl .= '&target-version='.rex_escape($targetVersion, 'url').'&'. $skipList->toUrl();;
$versionToggleUrl .= '&format='.$outputFormat.'&'. $skipList->toUrl();
$applyUrl .= '&'. $skipList->toUrl();

$result = RexFactor::runRexFactor($addon, $setList, $targetVersion, true, $skipList->toRectorSkipList());

$html = $content = $diffout = '';
$total = $result->getTotals();
if ($total['changed_files'] > 0) {
    $diffHtml = new DiffHtml($result, $outputFormat);
    $diff = DiffHtml::getHead();
    $diff .= $diffHtml->renderHtml();

    $content .= '<p>Target Version: '. rex_escape($targetVersion) .'</p>';
    $content .= '<p>Diff Format: '. $outputFormat .'</p>';

    if ($result instanceof \rexfactor\RectorResult) {
        $content .= '<p>Applied Rectors:';
        $content .= '<ul>';
        foreach($result->getAppliedRectors() as $appliedRector) {
            $content .= '<li>'. $appliedRector .' <a class="btn btn-xs btn-default" href="'. $skipUrl .'&'. $skipList->addSkipItem($appliedRector)->toUrl() .'">Skip</a></li>';
        }
        $content .= '</ul>';
        $content .= '</p>';

        if (count($urlSkipped) > 0) {
            $content .= '<p>Skipped Rectors:';
            $content .= '<ul>';
            foreach($urlSkipped as $skipped) {
                $content .= '<li>'. $skipped .' <a class="btn btn-xs btn-default" href="'. $skipUrl .'&'. $skipList->removeSkipItem($skipped)->toUrl() .'">Un-Skip</a></li>';
            }
            $content .= '</ul>';
            $content .= '</p>';
        }
    }

    $content .= ' <a class="btn btn-info" href="'. $backUrl .'">back</a>';
    $content .= ' <a class="btn btn-default" href="'. $formatToggleUrl .'">Change Format: '. $formatToggleLabel .'</a>';
    $content .= ' <a class="btn btn-default" href="'. $versionToggleUrl .'">Change Target-Version: '. $versionToggleLabel .'</a>';
    $content .= ' <a class="btn btn-save" href="'. $applyUrl .'" data-confirm="Source files will be overwritten. continue?">Apply changes</a>';

    $diffout = '<div style="margin-top: 10px"></div>';
    $diffout .= '<div style="background: unset; color: unset;">'.$diff.'</div>';
} else {
    $content .= '<h2>Code is shiny. Nothing todo for this migration - move along.</h2>';

    $content .= '<a class="btn btn-info" href="'. $backUrl .'">back</a>';
}
$fragment = new rex_fragment();
$fragment->setVar('title', 'Migration preview');
$fragment->setVar('body', $content, false);

echo '<h2>AddOn: '. $addonLabel .'</h2>';
if ($usecase = RexFactor::getUseCase($setList)) {
    echo '<h3>'.$usecase[0].': '.$usecase[1].'</h3>';
}

echo $fragment->parse('core/page/section.php');
echo $diffout;

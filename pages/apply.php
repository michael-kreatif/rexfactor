<?php

use rexfactor\RexFactor;
use rexfactor\SkipList;
use rexfactor\TargetVersion;

$addon = rex_get('addon', 'string');
$setList = rex_get('set-list', 'string');
$targetVersion = rex_get('target-version', 'string', TargetVersion::PHP7_2_COMPAT);
$urlSkipped = rex_get('skip', 'array[string]', []);
$skipList = SkipList::fromStrings($urlSkipped);

if ($addon === '') {
    throw new rex_exception('Missing addon parameter');
}

echo '<h2>AddOn: '. rex_escape($addon) .'</h2><hr>';
if ($usecase = RexFactor::getUseCase($setList)) {
    echo '<h3>'.$usecase[0].': '.$usecase[1].'</h3>';
}

$backToUseCaseUrl = rex_url::backendPage('rexfactor/use-case').'&addon='.rex_escape($addon, 'url');
$backToStartUrl = rex_url::backendPage('rexfactor');

$result = RexFactor::runRexFactor($addon, $setList, $targetVersion, false, $skipList->toRectorSkipList());

$total = $result->getTotals();
$content = '';
if ($total['changed_files'] > 0) {
    $content .= '
    <h4>Successfully migrated '. $total['changed_files'] .' files</h4>
    <ol>
        <li>
            At this point you should review and test the changed source-code.<br>
            You may use rexstan to detect potential issues.
        </li>
        <li style="margin-top: 10px">
            After making sure everything still works as expected commit the changes.
        </li>
        <li style="margin-top: 10px">
            <p>
            Finally you can go ahead with the next migration use-case:
            </p>
            <p>
            <a class="btn btn-info" href="'. $backToStartUrl .'">Start next migration for another AddOn</a>
            <a class="btn btn-info" href="'. $backToUseCaseUrl .'">Select next use-case for "'.rex_escape($addon).'"</a>
            </p>
        </li>
        </ol>
    ';
} else {
    $content .= '
    <h2>No changes</h2>
    <a class="btn btn-info" href="'. $backToStartUrl .'">Start next migration for another AddOn</a>
    <a class="btn btn-info" href="'. $backToUseCaseUrl .'">Select next use-case for "'.rex_escape($addon).'"</a>
    ';
}
$fragment = new rex_fragment();
$fragment->setVar('title', 'Changes applied');
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

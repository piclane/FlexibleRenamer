#!/usr/bin/php
<?php
require_once __DIR__ . '/cygwin.inc.php';
require_once __DIR__ . '/vendor/autoload.php';

$color = new \Colors\Color();
$cmd = buildCommand();
$dir = '';
$regexp = '';
$replacement = '';
if(strlen($cmd['preset'])) {
    $preset = parse_ini_file(__DIR__ . '/preset.ini', true);
    $presetName = $cmd['preset'];
    if(empty($preset[$presetName])) {
        die($color("Preset <{$presetName}> is not exist.\n")->bg('red')->bold()->white());
    }

    $preset = $preset[$presetName];
    $dir = $preset['dir'];
    $regexp = $preset['regexp'];
    $replacement = $preset['replacement'];
}
if(strlen($cmd['dir'])) {
    $dir = $cmd['dir'];
    $dir = _cp($dir);
    echo $dir;
}
if(strlen($cmd['regexp'])) {
    $regexp = $cmd['regexp'];
}
if(strlen($cmd['replacement'])) {
    $replacement = $cmd['replacement'];
}
if(empty($dir)) {
    die($color("No dir specified.\n")->bg('red')->bold()->white());
}
if(empty($regexp)) {
    die($color("No regexp specified.\n")->bg('red')->bold()->white());
}
if(empty($replacement)) {
    die($color("No replacement specified.\n")->bg('red')->bold()->white());
}

if (!($dh = opendir($dir))) {
    die("Can not open dir: {$dir}\n");
}

$queue = array();
while (($oldFile = readdir($dh)) !== false) {
    $newFile = $oldFile;
    if(is_array($regexp)) {
        foreach($regexp as $k=>$regexp_item) {
            $newFile = preg_replace($regexp_item, $replacement[$k], $newFile);
        }
    } else {
        $newFile = preg_replace($regexp, $replacement, $newFile);
    }
    if($oldFile != $newFile) {
        echo "   {$oldFile}\n-> {$newFile}\n\n";
        $queue[] = array(
            'old' => $oldFile,
            'new' => $newFile
        );
    }
}
closedir($dh);

echo "continue? (y/N)> ";
$prompt = fgets(STDIN, 4096);
if(strtolower(trim($prompt)) !== 'y') {
    echo "Aboted.\n";
    exit;
}

foreach($queue as $item) {
    rename("{$dir}/{$item['old']}", "{$dir}/{$item['new']}");
}
echo "complate.\n";

/**
 * コマンドラインからのオプションパーサーを取得します
 *
 * @return \Commando\Command オプションパーサー
 */
function buildCommand($argv = null) {
    $cmd = new Commando\Command($argv);

    $cmd->option('p')
        ->aka("preset")
        ->describe("プリセット名を指定します");

    $cmd->option('i')
        ->aka('dir')
        ->aka('input')
        ->describe("入力ディレクトリ");

    $cmd->option('regexp')
        ->describe("正規表現");

    $cmd->option('replacement')
        ->describe('置換文字列');

    return $cmd;
}
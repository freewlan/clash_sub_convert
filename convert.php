<?php
$ruleUrl = $_GET['rule_url'];
$subUrl = $_GET['sub_url'];
$filter = $_GET['filter'];

$ruleContent = file_get_contents($ruleUrl);
$clash = yaml_parse($ruleContent);

$subContent = file_get_contents($subUrl);
$clashOri = yaml_parse($subContent);

if ($filter) {
    $proxyList = [];
    foreach ($clashOri['Proxy'] as $proxy) {
        if (strstr($proxy['name'], $filter)) {
            $proxyList[] = $proxy;
        }
    }
    $clash['Proxy'] = $proxyList;
} else {
    $clash['Proxy'] = $clashOri['Proxy'];
}

$proxyListName = array_column($clash['Proxy'], 'name');

$group = [];
foreach ($clash['Proxy Group'] as $v) {
    $v['proxies'] = $proxyListName;
    if (strtoupper($v['name']) == 'PROXY') {
        $v['proxies'][] = 'auto';
    }

    $group[] = $v;
}

$clash['Proxy Group'] = $group;

exit(yaml_emit($clash));

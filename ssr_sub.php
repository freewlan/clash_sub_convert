<?php

function decodeBase64($str)
{
    $str = str_replace("_", "/", $str);
    $str = str_replace("-", "+", $str);
    return base64_decode($str);
}

if (isset($_GET['dns']) && $_GET['dns'] == 'disable') {
    $dns = false;
} else {
    $dns = true;
}
if (!isset($_GET['url'])) {
    exit('地址错误');
}
$fileContents = file_get_contents($_GET['url']);
$fileContents = base64_decode($fileContents);

$ssrList = explode("\n", $fileContents);
unset($ssrList[0]);
unset($ssrList[1]);
$ssrList = array_filter($ssrList);
$node = [];
foreach ($ssrList as $ssr) {
    $prefix = "ssr://";
    $ssr = str_replace($prefix, '', $ssr);

    $ssr = decodeBase64($ssr);
    $ssr = explode(':', $ssr);

    $server = $ssr[0];
    $port = $ssr[1];
    $protocol = $ssr[2];
    $method = $ssr[3];
    $obfs = $ssr[4];

    $remark = explode('&', $ssr[5]);

    $obfsparam = decodeBase64(explode('/?obfsparam=', $remark[0])[1]);
    $password = decodeBase64(explode('/?obfsparam=', $remark[0])[0]);
    $protoparam = decodeBase64(explode('protoparam=', $remark[1])[1]);
    $remarks = decodeBase64(explode('remarks=', $remark[2])[1]);

    $node[] = [
        'name' => str_replace('&', '', $remarks),
        'type' => 'ssr',
        'server' => $server,
        'port' => $port,
        'cipher' => $method,
        'password' => $password,
        'obfs' => $obfs,
        'protocol' => $protocol,
        'obfs-param' => $obfsparam,
        'protocol-param' => $protoparam,
        'udp' => substr($remarks, 4, 1) == 'U',
    ];
}

$node[] = [
    'name' => 'socks',
    'type' => 'socks5',
    'server' => '127.0.0.1',
    'port' => '1086',
];

$fileContent = file_get_contents('./relay_template.yaml');
$clashObj = yaml_parse($fileContent);

$group = [];
foreach ($clashObj['proxy-groups'] as $v) {
    if ($v['name'] == 'node') {
        $v['proxies'] = array_column($node, 'name');
    }
    $group[] = $v;
}

$clashObj['allow-lan'] = true;
$clashObj['external-controller'] = '0.0.0.0:9090';
$clashObj['proxies'] = $node;
$clashObj['proxy-groups'] = $group;
$clashObj['dns']['enable'] = $dns;

unset($clashObj['proxy-providers']);
exit(yaml_emit($clashObj, YAML_UTF8_ENCODING));
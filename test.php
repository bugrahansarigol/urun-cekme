<?php

require_once __DIR__ . "/n11.php";
require_once __DIR__ . "/gittigidiyor.php";
require_once __DIR__ . "/ciceksepeti.php";

if (isset($_GET['n'])) {
    $url = $_GET['n'];
    $n11 = new n11();
    $product = $n11->getProduct($url);
} elseif (isset($_GET['g'])) {
    $url = $_GET['g'];
    $gittigidiyor = new gittigidiyor();
    $product = ($gittigidiyor->getProduct($url));
} elseif (isset($_GET['c'])) {
    $url = $_GET['c'];
    $ciceksepeti = new ciceksepeti();
    $product = $ciceksepeti->getProduct($url);
}
var_dump($product);

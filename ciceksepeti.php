<?php
require_once __DIR__ . "/simple_html_dom.php";

class ciceksepeti
{

    public function getProduct($url)
    {

        return $this->first($url);
    }

    public function first($url)
    {
        $base = $url;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_URL, $base);
        curl_setopt($curl, CURLOPT_REFERER, $base);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $str = curl_exec($curl);
        curl_close($curl);

        $html = new simple_html_dom();
        $html->load($str);
        $variantList = [];

        //Varyant id filtreleme
        foreach ($html->find('.product__variants ul') as $a) {
            if ($a->attr['class'] == "multiple-variants") {//Renk,beden vs ilişkili ise
                $relations = $html->find('div.js-product-detail-relations', 0);
                preg_match_all('/\[\{.*?}]/s', $relations, $json);
                $arr = json_decode($json[0][0], true);
                foreach ($a->find('input[data-is-color=true]') as $b) {
                    $colorCode = $b->attr['data-value-id'];
                    foreach ($arr as $item) {
                        if (array_search($colorCode, $item['detailValueIds']) !== false) {
                            array_push($variantList, $item['variantId']);
                            break;
                        }
                    }
                }
            } elseif ($a->attr['class'] == "variants") {//Tek varyant tipi varsa
                $variants = explode(",", $html->find('.js-variant-list', 0)->attr['value']);
                $variantList = $variants;
            }
        }
        //Fotograf && Fiyat && Döviz Kur && Başlık
        foreach ($html->find('head script') as $a) {
            if (strstr($a, "productThumbs")) {
                $data['urun_fotograf'] = [];
                preg_match_all('/\[\{.*?}]/s', $a->innertext, $JSONvariants);
                $json = $JSONvariants[0][0];
                $variants = json_decode($json, true);
                foreach ($variants as $variant) { //varyant listesindeki fotoları almak için
                    if ((array_search($variant['FullSizeImageUrl'], $data['urun_fotograf']) !== true)
                        &&
                        (array_search($variant['VariantId'], $variantList) !== false)) {

                        array_push($data['urun_fotograf'], $variant['FullSizeImageUrl']);
                    } elseif (count($variantList) == 0 // Hiç bir varyant yoksa sadece fotoları almak için
                        && array_search($variant['FullSizeImageUrl'], $data['urun_fotograf']) !== true) {
                        array_push($data['urun_fotograf'], $variant['FullSizeImageUrl']);
                    }
                }
            }
            if (strstr($a, 'dataLayer = [')) {
                preg_match_all('/\[\{.*?}]/s', $a->innertext, $JSONvariants);
                $dataLayer = json_decode($JSONvariants[0][0], true)[0];
                $data['urun_adi'] = $dataLayer['productName'];
                $data['urun_satisfiyati'] = $dataLayer['productTotalPrice'];
//                $data['urun_dovizkur'] = $dataLayer['currency'];
            }
            if (strstr($a, "priceCurrency")) {
                $arr = json_decode($a->innertext, true);
                $data['urun_dovizkur'] = $arr['offers']['priceCurrency'];
            }

        }
        //Kategori
        foreach ($html->find('script[type="application/ld+json"]') as $a) {
            if (strstr($a, "BreadcrumbList")) {
                preg_match_all('/\[\{.*?}]/s', $a->innertext, $json);
                $breadcrumbList = json_decode($json[0][0], true);
                $bc = "";
                foreach ($breadcrumbList as $index => $breadcrumb) {
                    if ($index != 0) {
                        if (end($breadcrumbList) == $breadcrumb) {
                            $bc .= ($breadcrumb['item']['name']);
                        } else {
                            $bc .= ($breadcrumb['item']['name']) . ">";
                        }
                    }
                }
                $data['urun_kategori'] = $bc;
            }
        }
        //Ürün Detay
        foreach ($html->find('.product__description .product__recipe-body') as $index => $a) {
            if ($a->find('.product__description-text')) {
                foreach ($a->find('.product__description-text') as $b) {
                    if ($b->attr['class'] != "hidden") {
                        $data['urun_entdetayaciklama'] = trim(htmlspecialchars($b));
                    }
                }
            } elseif ($a->find('.product__specifications')) {
                foreach ($a->find('.product__specifications') as $b) {
                    if ($b->attr['class'] != "hidden") {
                        $data['urun_ozellik'] = trim(htmlspecialchars($b));
                    }
                }
            }

        }
        return $data;
    }
}
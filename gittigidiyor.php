<?php
require_once __DIR__ . "/simple_html_dom.php";

class gittigidiyor
{

    public function getProduct($url)
    {
//        $url = $_GET['d'];
        return $this->first($url);
    }

    function getPhotos($urls)
    {
        $imgD = [];
        foreach ($urls as $url) {
            $context = stream_context_create(array(
                'http' => array(
                    'header' => array('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201'),
                ),
            ));
            $html = file_get_html($url, false, $context);
            foreach ($html->find('#gallery .gallery-hidden-thumbs .product-photos-ul li') as $b) { //Fotograf Bölümü
                $img = str_replace("tn14", "tn50", $b->find('img', 0)->attr['data-original']);
                if (array_search($img, $imgD) === false) {
                    array_push($imgD, $img);
                }
            }
        }
        return $imgD;
    }

    function first($url)
    {
        $base = $url;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_URL, $base);
        curl_setopt($curl, CURLOPT_REFERER, $base);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $str = curl_exec($curl);
        curl_close($curl);

        $html = new simple_html_dom();
        $html->load($str);
//        echo $html;
//        $context = stream_context_create(array(
//            'http' => array(
//                'header' => array('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201'),
//            ),
//        ));
//        $html = file_get_html($url, false, $context);

        //Fotograf & Başlık & Fiyat
        foreach ($html->find('div.boxContent') as $a) {// Ürün Kartı
            //Fotograf
            $data['urun_fotograf'] = [];
            $urls = [];
            if ($a->find('.sp-specContainer', 0)) {
                foreach ($a->find('#sp-spec-options .sp-specContainer') as $b) {
                    foreach ($b->find('.sp-specOption-scroll .sp-specOption .sp-specOptionValue') as $c) {
                        if (!strstr($c->attr['class'], "disabled")) {
                            if (strstr($url, "?")) {
                                $url = explode("?", $url)[0];
                            }
                            $vUrl = $url . "?" . $c->find('input.sp-specOptionValue-actionQuery', 0)->attr['value'];
                            array_push($urls, $vUrl);
                        }
                    }
                }
                $data['urun_fotograf'] = $this->getPhotos($urls);
            } else {
                array_push($urls, $url);
                $data['urun_fotograf'] = $this->getPhotos($urls);
            }
            //Başlık
            foreach ($a->find('#badgeTitleReviewBrand .title-container .h1-container') as $b) {//Başlık Bölümü
                $data['urun_adi'] = trim($b->find('#sp-title', 0)->plaintext);
                trim($b->find('#sp-subTitle', 0)->plaintext)
                    ? $data['urun_altbaslik'] = trim($b->find('#sp-subTitle', 0)->plaintext)
                    : "";
            }
            //Fiyat
            foreach ($a->find('.seller-price-area') as $b) { //Fiyat, Varyant vs Bölümü
                $data['urun_satisfiyati'] = explode(" ", trim($b->find('.lastPrice', 0)->plaintext))[0];
            }
        }
        //Döviz Kur
        foreach ($html->find('head meta') as $a) {
            if (strstr($a, "og:price:currency")) {
                $data['urun_dovizkur'] = $a->attr['content'];
            }
        }
        //Kategori
        $bc = "";
        foreach ($html->find('#breadcrumb .hidden-breadcrumb') as $index => $a) {
            $breadcrumbs = $a->find('li');
            foreach ($breadcrumbs as $i => $breadcrumb) {
                if ($i !== 0) {
                    if (end($breadcrumbs) === $breadcrumb) {
                        $bc .= trim($a->find('a', $i)->plaintext);
                    } else {
                        $bc .= trim($a->find('a', $i)->plaintext) . ">";
                    }
                }

            }
            $data['urun_kategori'] = $bc;
        }
        //Ürün Detay & varsa Özellik
        foreach ($html->find('#product-information') as $a) {
            foreach ($a->find('#urun-ozellikleri #catalog-info-details') as $b) {
                $data['urun_ozellik'] = trim(htmlspecialchars($b));
            }
            foreach ($a->find('#satici-aciklamasi #specs-container') as $b) {
                $data['urun_entdetayaciklama'] = trim(htmlspecialchars($b));
            }
        }
        return $data;
    }
}
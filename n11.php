<?php
require_once __DIR__ . "/simple_html_dom.php";

class n11
{
    public function getProduct($url)
    {
        //Url parçalama
        $expUrl = explode('//', $url); //www
        $expUrl = explode('.', $expUrl[1]);//urun

        if ($this->page($url) == "giybimoda") {
            return $this->third($url);
        } elseif ($this->page($url) == "n11") {
            if ($expUrl[0] == 'www') {
                return $this->first($url);
            } elseif ($expUrl[0] == 'urun') {
                return $this->second($url);
            }
        } elseif ($this->page($url) == "market11") {
            echo "Market 11 desteklenmiyor";
        }

    }

    function page($url)
    {
        if (strstr($url, "market11")) {
            return "market11";
        }
        $html = file_get_html($url);
        $result = trim($html->find('div#breadCrumb .clearfix li', 0)->plaintext);
        if ($result == "giybiModa") {
            return "giybimoda";
        } elseif ($result == "Ana Sayfa") {
            return "n11";
        }
    }

    function htmlToPlainText($str)
    {
        $str = str_replace('&nbsp;', ' ', $str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_COMPAT, 'UTF-8');
        $str = html_entity_decode($str, ENT_HTML5, 'UTF-8');
        $str = html_entity_decode($str);
        $str = htmlspecialchars_decode($str);
        $str = strip_tags($str);

        return $str;
    }

    function getPhoto($urls)
    {
        $imgD = [];
        foreach ($urls as $url) {
            $html = file_get_html($url);
            foreach ($html->find('.unf-p-thumbs') as $a) {
                foreach ($a->find('li[data-full]') as $b) {
                    $img = $b->attr['data-full'];
                    if (array_search($img, $imgD) === false) {
                        array_push($imgD, $img);
                    }
                }
            }
        }
        return $imgD;
    }

    function getPhotoModa($urls)
    {
        $imgD = [];
        foreach ($urls as $url) {
            $html = file_get_html($url);
            if ($html->find('.thumbsHolder', 0)) {
                foreach ($html->find('.thumbsHolder ul') as $a) {
                    foreach ($a->find('li[data-full]') as $b) {
                        $img = $b->attr['data-full'];
                        if (array_search($img, $imgD) === false) {
                            array_push($imgD, $img);
                        }
                    }
                }
            } else {
                array_push($imgD, $html->find('figure.proImgHolder .pro-image-contain a.easyzoom--overlay', 0)->attr['href']);
            }

        }
        return $imgD;

    }

    function third($url) //Moda
    {
        $context = stream_context_create(array(
            'http' => array(
                'header' => array('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201'),
            ),
        ));
        $html = file_get_html($url, false, $context);
        //Fotograf
        //Variant varsa
        $urls = [];
        //Fotograf
        if ($html->find('.fashion-variant', 0)) {
            $data['urun_fotograf'] = [];
            foreach ($html->find('.fashion-variant') as $a) {
                foreach ($a->find('.fashion-variant__thumbs .fashion-variant__thumb') as $b) {
                    array_push($urls, $b->find('img', 0)->attr['data-product-url']);
                }
                $data['urun_fotograf'] = $this->getPhotoModa($urls);
            }
        }//Variant yoksa
        else {
            array_push($urls, $url);
            $data['urun_fotograf'] = $this->getPhotoModa($urls);

        }
        //Başlıklar
        foreach ($html->find('section.pro-detail-part') as $a) { //Ürün kartı
            foreach ($a->find('div.pro-prop .pro-prop-top-area .pro-prop-top-title .pro-title') as $b) {
                $data['urun_adi'] = $b->find('h1', 0)->plaintext;
            }
        }
        //Fiyat & Kategori
        foreach ($html->find('script[type=application/ld+json]') as $a) { //script tag
            //Kategori
            if (strstr($a->innertext, 'BreadcrumbList')) {
                preg_match_all('/\[\{.*?]/s', $a->innertext, $breadcrumbs);
                $breadcrumbs = json_decode($breadcrumbs[0][0], true);
                $bc = "";
                foreach ($breadcrumbs as $index => $breadcrumb) {
                    if ($index != "0") {
                        if (end($breadcrumbs) == $breadcrumb) {
                            $bc .= $breadcrumb['name'];
                        } else {
                            $bc .= $breadcrumb['name'] . ">";
                        }
                    }
                }
                $data['urun_kategori'] = $bc;
            }

            //Fiyat
            if (strstr($a->innertext, 'lowPrice')) {
                $json = str_replace(' ', '', $a->innertext);
                $json = explode(',', $json);
                foreach ($json as $j) {
                    if (strstr($j, 'lowPrice')) {
                        $data['urun_satisfiyati'] = explode('"', $j)[3];
                    } elseif (strstr($j, 'priceCurrency')) {
                        $data['urun_dovizkur'] = strtoupper(explode('"', $j)[3]);
                    }
                }
            }
        }
        //Ürün Detay
        foreach ($html->find('div.product-information') as $a) { //Ürün açıklama kartı
            //Ürün Detay
            foreach ($a->find('.pro-detail_sub-details') as $b) {
                if ($b->find('.lazy')) {
                    foreach ($b->find('.lazy') as $c) {
                        $c->src = $c->attr['data-src'];
                    }
                }
                $data['urun_entdetayaciklama'] = htmlspecialchars(trim($b->find('div', 1)));
            }

            //Ürün Özellikleri
//        $data['Ürün Özellikleri'] = [];
//        foreach ($a->find('.info-container .info-box') as $b) {
//            $data['Ürün Özellikleri'][trim($b->find('.info-title', 0)->plaintext)] = trim($b->find('p,div', 0)->plaintext);
//
//        }
        }
        //Varyant
//    foreach ($html->find('script[type=text/javascript]') as $index => $a) { //script tag
//        if (strstr($a->innertext, 'variants')) {
//            $data['Varyant'] = [];
//            $ret = $a->innertext();
//            //CDATA kaldırma
//
//            $ret = str_ireplace('<![CDATA[', '', $ret);
//            $ret = str_replace(']]>', '', $ret);
//
//            //Variants'dan sonrasını bölme
//            $ret = explode('variants', htmlspecialchars($a))[1];
//
//            //Özel karakterleri temizleme
//            $ret = htmlToPlainText($ret);
//
//            //variants'in json değerini alma
//            preg_match_all('/\{.*?}/s', $ret, $JSONvariants);
//
//            //Variantları dolaşma
//            foreach ($JSONvariants[0] as $index => $var) {
//                if ($var != "{}") {
//                    $variant = json_decode($var, true);
//                    $data['Varyant'][$variant['name']] = [];
//                    foreach ($variant['values'] as $value) {
//                        array_push($data['Varyant'][$variant['name']], $value);
//                    }
//                }
//            }
//        }
//    }
        //Fiyat & Kategori
        //Kategori & Fiyat
        return $data;
    }

    function second($url) //urun.n11
    {
        $context = stream_context_create(array(
            'http' => array(
                'header' => array('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201'),
            ),
        ));
        $html = file_get_html($url, false, $context);
        //Başlık & Fotograf
        foreach ($html->find('div.proDetailArea') as $a) { //Ürün kartı
            //Başlıklar
            foreach ($a->find('div.proNameHolder .nameHolder') as $b) {
                $data['urun_adi'] = trim($b->find('h1', 0)->plaintext);
                $data['urun_altbaslik'] = trim($b->find('h2', 0)->plaintext);
            }

            //Fotograf
            $data['urun_fotograf'] = [];
            //Çok Fotograflıysa
            if ($a->find("div.proDetailSocialBar .proImgHolder .thumbsHolder")) {
                foreach ($a->find('div.proDetailSocialBar .proImgHolder .thumbsHolder ul') as $b) {
                    foreach ($b->find('li') as $c) {//li
                        array_push($data['urun_fotograf'], $c->attr['data-thumb']);
                    }
                }
            } //Tek Fotograf varsa
            else {
                array_push($data['urun_fotograf'], $a->find('div.proDetailSocialBar .proImgHolder .pro-image-contain img', 0)->attr['data-src']);
            }

        }
        //Fiyat & Kategori
        foreach ($html->find('script[type=application/ld+json]') as $a) { //script tag

            //Kategori
            if (strstr($a->innertext, 'BreadcrumbList')) {
                preg_match_all('/\[\{.*?]/s', $a->innertext, $breadcrumbs);
                $breadcrumbs = json_decode($breadcrumbs[0][0], true);
                $bc = "";
                foreach ($breadcrumbs as $index => $breadcrumb) {
                    if ($index != "0") {
                        if (end($breadcrumbs) == $breadcrumb) {
                            $bc .= $breadcrumb['name'];
                        } else {
                            $bc .= $breadcrumb['name'] . ">";
                        }
                    }
                }
                $data['urun_kategori'] = $bc;
            }

            //Fiyat
            if (strstr($a->innertext, 'lowPrice')) {
                $json = str_replace(' ', '', $a->innertext);
                $json = explode(',', $json);
                foreach ($json as $j) {
                    if (strstr($j, 'lowPrice')) {
                        $data['urun_satisfiyati'] = explode('"', $j)[3];
                    } elseif (strstr($j, 'priceCurrency')) {
                        $data['urun_dovizkur'] = strtoupper(explode('"', $j)[3]);
                    }
                }
            }
        }
        //Ürün Detay
        foreach ($html->find('div#tabPanelProDetail .panelContent') as $a) {
            foreach ($a->find('.details') as $b) {
                foreach ($b->find('.lazy') as $c) {
                    $c->src = $c->attr['data-src'];
                }
                $data['urun_entdetayaciklama'] = htmlspecialchars(trim($b->find('div', 0)));
            }

        }
        //Varyant
//    foreach ($html->find('script[type=text/javascript]') as $index => $a) { //script tag
//        if (strstr($a->innertext, 'variants')) {
//            $data['Varyant'] = [];
//            $ret = $a->innertext();
//            //CDATA kaldırma
//
//            $ret = str_replace('<![CDATA[', '', $ret);
//            $ret = str_replace(']]>', '', $ret);
//
//            //Variants'dan sonrasını bölme
//            $ret = explode('variants', htmlspecialchars($a))[1];
//
//            //Özel karakterleri temizleme
//            $ret = htmlToPlainText($ret);
//
//            //variants'in json değerini alma
//            preg_match_all('/\{.*?}/s', $ret, $JSONvariants);
//
//            //Variantları dolaşma
//            foreach ($JSONvariants[0] as $index => $var) {
//                if ($var != "{}") {
//                    $variant = json_decode($var, true);
//                    $data['Varyant'][$variant['name']] = [];
//                    foreach ($variant['values'] as $value) {
//                        array_push($data['Varyant'][$variant['name']], $value);
//                    }
//                }
//            }
//        }
//    }
        //Ürün Özellik & Detay
//    $data['Ürün Özellikleri'] = [];

        //Ürün Özellik
//        foreach ($a->find('.features div .feaItem') as $index => $b) {
//            $data[trim($a->find('h4', 0)->plaintext)][trim($b->find('.label', 0)->plaintext)] = [];
//            $data['Ürün Özellikleri'][trim($b->find('.label', 0)->plaintext)] = [];
//            foreach ($b->find('.data') as $i => $c) {
//                $data['Ürün Özellikleri'][trim($b->find('.label', 0)->plaintext)] = trim($b->find('.data', $i)->plaintext);
//            }
//        }
        return $data;
    }

    function first($url) // n11.com
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
        $html = file_get_html($url);
        //Başlık & Fotograf
        foreach ($html->find('div.proDetailArea .unf-p-lBox') as $e) { //Ürün kartı
            //Başlıklar
            foreach ($e->find('div.unf-p-detail .unf-p-title .nameHolder') as $a) {
                $data['urun_adi'] = trim($a->find('.proName', 0)->plaintext);
                $data['urun_altbaslik'] = trim($a->find('.proSubName', 0)->plaintext);
            }
            //Fotograf
            $urls = [];
            //Varyant varsa
            if ($e->find('.unf-p-sku-slct', 0)) {
                foreach ($e->find('div.unf-p-detail .unf-p-sku-slct') as $a) {
                    //Varyant varsa
                    foreach ($a->find('ul[data-unif-seovari]') as $b) {
                        $pureUrl = $url;
                        $pureUrl .= "&" . $b->attr['data-unif-seovari'];

                        foreach ($b->find('li') as $c) {
                            $newUrl = $pureUrl . "=" . $c->find('span', 0)->attr['data-unif-seovalue'];
                            array_push($urls, $newUrl);
                        }
                    }
                    $data['urun_fotograf'] = $this->getPhoto($urls);
                }
            } //Varyant yoksa
            else {
                array_push($urls, $url);
                $data['urun_fotograf'] = $this->getPhoto($urls);
            }
        }
        //Kategori ve Fiyat
        foreach ($html->find('script[type=application/ld+json]') as $a) { //script tag
            //Kategori
            if (strstr($a->innertext, 'BreadcrumbList')) {
                preg_match_all('/\[\{.*?]/s', $a->innertext, $breadcrumbs);
                $breadcrumbs = json_decode($breadcrumbs[0][0], true);
                $bc = "";
                foreach ($breadcrumbs as $index => $breadcrumb) {
                    if ($index != "0") {
                        if (end($breadcrumbs) == $breadcrumb) {
                            $bc .= $breadcrumb['name'];
                        } else {
                            $bc .= $breadcrumb['name'] . ">";
                        }
                    }
                }
                $data['urun_kategori'] = $bc;
            }
            //Fiyat
            if (strstr($a->innertext, 'lowPrice')) {
                $json = str_replace(' ', '', $a->innertext);
                $json = explode(',', $json);
                foreach ($json as $j) {
                    if (strstr($j, 'lowPrice')) {
                        $data['urun_satisfiyati'] = explode('"', $j)[3];
                    } elseif (strstr($j, 'priceCurrency')) {
                        $data['urun_dovizkur'] = strtoupper(explode('"', $j)[3]);
                    }
                }
            }
        }
        //Ürün detayı varsa
        if ($html->find('div#unf-info')) {
            foreach ($html->find("div#unf-info .unf-info-context") as $a) {
                if ($a->find('.lazy')) {
                    foreach ($a->find('.lazy') as $b) {
                        $b->src = $b->attr['data-src'];
                    }
                }
                $data['urun_entdetayaciklama'] = htmlspecialchars(trim($a));
            }
        }
        //Ürün Özellikleri
//    foreach ($html->find('div#unf-prop') as $a) { //ul
//        $data['Ürün Özellikleri'] = [];
//        foreach ($a->find('.unf-prop-context .unf-prop-list li') as $index => $e) { //li
//            $data['Ürün Özellikleri'][trim($e->find('p', 0)->plaintext)] = trim($e->find('p', 1)->plaintext);
//        }
//    }
        return $data;


    }
}
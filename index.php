<?php
#####  Hata Denetimi  #####
error_reporting(0);
//error_reporting(E_ALL);


#####  Zaman Dilimi  #####
date_default_timezone_set('Europe/Istanbul');


#####  Sabitler  #####
define('KONTROL', true);
define('SITE_PATH', dirname(__FILE__));
define('SITE_URL', 'http://127.0.0.1/namazvakitleri'); // SITE URL


require 'function.php';
$islem=@$_GET['islem'];
$ulke_id=@(int)$_GET['ulke_id'];
$sehir_id=@(int)$_GET['sehir_id'];
$ilce_id=@(int)$_GET['ilce_id'];
$periyot=@$_GET['periyot'];


switch($islem){
    
    case 'getSehirList':
        header("Content-type: application/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        echo getSehirListFunc($ulke_id);
    break;

    case 'getIlceList':
        header("Content-type: application/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        echo getIlceListFunc($sehir_id);
    break;

    case 'getNamazVakitleri':
        header("Content-type: application/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        echo getNamazVakitleri($ulke_id,$sehir_id,$ilce_id,$periyot);
    break;

    default:
        header("Access-Control-Allow-Origin: *");
        anasayfa();
    break;

}
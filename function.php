<?php if(!defined('KONTROL')){ echo 'Bu dosyaya erşiminiz engellendi.'; exit(); } 

function json_turkce($dizi)
{ 
    foreach($dizi as $record){ 
        foreach($record as $key=>$og){ 
            $colm[]='"'.$key.'":"'.$og.'"'; 
        } 
        $rec[]="{".implode(",",$colm)."}"; 
        unset($colm); 
    } 
    $sonuc='['.implode(",",$rec).']'; 
    return $sonuc; 
} 


function boslukTemizle($data)
{
    $data=str_replace('    ',' ',$data);
    $data=str_replace('    ',' ',$data);
    $data=str_replace('   ',' ',$data);
    $data=str_replace('   ',' ',$data);
    $data=str_replace('  ',' ',$data);
    $data=str_replace('  ',' ',$data);
    return explode(' ',trim($data));
}


function getNamazVakitleri($ulke_id,$sehir_id,$ilce_id='',$periyot='')
{
    require_once SITE_PATH.'/simple_html_dom.php';
    
    $postdata = http_build_query( array('Country' => $ulke_id, 'State' => $sehir_id, 'City' => $ilce_id, 'period'=>'Aylik') );
    $result=curl_post('http://www.diyanet.gov.tr/tr/PrayerTime/PrayerTimesList', $postdata);
    $html=str_get_html($result);
    
    $deneme=array();
    $deneme[0]=boslukTemizle(@$html->find('table tr',0)->plaintext);
    $deneme[1]=boslukTemizle(@$html->find('table tr',1)->plaintext);
    
    $deneme[0][1]='Imsak';
    $deneme[0][2]='Gunes';
    $deneme[0][3]='Ogle';
    $deneme[0][4]='Ikindi';
    $deneme[0][5]='Aksam';
    $deneme[0][6]='Yatsi';
    $deneme[0][7]='Kible';
    
    if ($periyot==''){
        
        // Günlük
        if (empty($deneme[1])){
            return '[{"error":"Namaz vakitlerine ulaşılamadı. Lütfen tekrar deneyiniz."}]';
        }else{
            return json_turkce(array(array_combine($deneme[0],$deneme[1])));

        }        
        
    }elseif($periyot=='haftalik'){

        // Haftalık
        $combine=array();
        
        for ($i=1; $i < 8; $i++) {
            
            $veri=array_combine($deneme[0],boslukTemizle($html->find('table tr',$i)->plaintext));
            
            if ($veri){
                $combine[]=$veri;
            }
        }
        
        echo json_encode($combine);
        
    }elseif($periyot=='aylik'){

        // Aylık
        $combine=array();
        
        for ($i=1; $i < 34; $i++) {
            
            $veri=array_combine($deneme[0],boslukTemizle($html->find('table tr',$i)->plaintext));
            
            if ($veri){
                $combine[]=$veri;
            }
        }
        
        echo json_encode($combine);
    }
}


function getSehirListFunc($ulke_id)
{
    $ulke_id=(int)$ulke_id;
    $sehirler_json=curl_file_get_contents('http://www.diyanet.gov.tr/PrayerTime/FillState?countryCode='.$ulke_id);
    
    if ($sehirler_json=='[]'){

        return '[{"error":"Bu ulkeye ait sehirler bulunamadi."}]';
        
    }else{
        $sehirler_json=str_replace('"Selected":false,','',$sehirler_json);
        $sehirler_json=str_replace('"Selected":true,','',$sehirler_json);
        $sehirler_json=str_replace('"Text":','"SehirAdi":',$sehirler_json);
        $sehirler_json=str_replace('"Value":','"SehirId":',$sehirler_json);

        return $sehirler_json;
    }
}


function getIlceListFunc($sehir_id)
{
    $sehir_id=(int)$sehir_id;

    $ilceler_json=curl_file_get_contents('http://www.diyanet.gov.tr/PrayerTime/FillCity?itemId='.$sehir_id);
    
    if ($ilceler_json=='[]'){

        return '[{"error":"Bu sehire ait ilceler bulunamadi."}]';
        
    }else{
        $ilceler_json=str_replace('"Selected":false,','',$ilceler_json);
        $ilceler_json=str_replace('"Selected":true,','',$ilceler_json);
        $ilceler_json=str_replace('"Text":','"IlceAdi":',$ilceler_json);
        $ilceler_json=str_replace('"Value":','"IlceId":',$ilceler_json);

        return $ilceler_json;        
    }

}


function anasayfa()
{
?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="utf-8">
        <title>Ahmeti Namaz Vakitleri API</title>
        <style type="text/css">
            #namaz_vakitleri{width: 640px;margin: 10px auto;border-collapse: collapse;border-spacing: 0;}
            #namaz_vakitleri th{border: 1px solid #ddd;background-color: antiquewhite}
            #namaz_vakitleri td{border: 1px solid #ddd;}
            .red{color: red;font-weight: bold}
            .green{color: green;font-weight: bold}
            .orange{color: orange;font-weight: bold}
        </style>
        <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
        <script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
        <script type="text/javascript">
            
            var site_url="<?php echo SITE_URL; ?>";
            
            $( document ).ready(function() {

                var ulke_id='';
                var sehir_id='';
                var ilce_id='';
                var href_a='';
                var text_a='';

                $('#select_ulke').change(function() {


                    $('#select_sehir_tr').hide();
                    $('#uyari_p').hide();
                    $('#gonder_button').hide();
                    $('#select_ilce_tr').hide();
                    sehir_id='';
                    ilce_id='';
                    linkKapat();
                    $( '#inside_nmz_table' ).empty().hide();

                    ulke_id=$('#select_ulke').val();

                    if(ulke_id > 0){
                        $.ajax({
                            type: 'GET',
                            url: site_url+"/index.php",
                            data: 'islem=getSehirList&ulke_id='+ulke_id,
                            dataType: 'json',
                            success: function( data ) {

                                if (data[0].error !== undefined){

                                    // Sehir Yok...
                                    //alert(data[0].error);
                                    $('#uyari_p').empty().text('Bu ülkeye ait şehir bulunmamaktadır. Ülkeye göre sorgulama yapabilirsiniz.');
                                    $('#uyari_p').show();
                                    //$('#gonder_button').show();
                                    //linkYaz(site_url,ulke_id,sehir_id,ilce_id);

                                }else{
                                    $('#select_sehir').empty();
                                    $('#select_ilce').empty();
                                    $('<option value="">Seçiniz...</option>').appendTo( '#select_ilce' );

                                    $('#select_sehir_tr').show();
                                    $('<option value="">Seçiniz...</option>').appendTo( '#select_sehir' );

                                    $.each(data, function(i, item) {
                                        $('<option value="'+data[i].SehirId+'">'+data[i].SehirAdi+'</option>').appendTo( '#select_sehir' );
                                    });
                                    //linkYaz(site_url,ulke_id,sehir_id,ilce_id);
                                }
                            }
                        });
                    }
                });




                $('#select_sehir').change(function() {

                    $('#select_ilce_tr').hide();
                    $('#uyari_p').hide();
                    $('#gonder_button').hide();
                    ilce_id='';
                    linkKapat();
                    $( '#inside_nmz_table' ).empty().hide();

                    sehir_id=$('#select_sehir').val();

                    if(sehir_id > 0){

                        $.ajax({
                            type: 'GET',
                            url: site_url+"/index.php",
                            data: 'islem=getIlceList&sehir_id='+sehir_id,
                            dataType: 'json',
                            success: function( data ) {

                                if (data[0].error !== undefined){

                                    // Ilce Yok...
                                    //alert(data[0].error);
                                    $('#uyari_p').empty().text('Bu şehire ait ilçe bulunmamaktadır. Şehire göre sorgulama yapabilirsiniz.');
                                    $('#uyari_p').show();
                                    $('#gonder_button').show();
                                    linkYaz(site_url,ulke_id,sehir_id,ilce_id);

                                }else{
                                    $('#select_ilce').empty();
                                    $('#select_ilce_tr').show();
                                    $('<option value="">Seçiniz...</option>').appendTo( '#select_ilce' );

                                    $.each(data, function(i, item) {
                                        $('<option value="'+data[i].IlceId+'">'+data[i].IlceAdi+'</option>').appendTo( '#select_ilce' );
                                    });
                                    //$('#gonder_button').show();
                                    //linkYaz(site_url,ulke_id,sehir_id,ilce_id);
                                }
                            }
                        });
                    }
                });


                $('#select_ilce').change(function() {
                    ilce_id=$('#select_ilce').val();
                    $( '#inside_nmz_table' ).empty().hide();
                    $('#gonder_button').show();
                    linkYaz(site_url,ulke_id,sehir_id,ilce_id);
                });


                $( "#gonder_button" ).click(function() {

                    ulke_id=$('#select_ulke').val();
                    sehir_id=$('#select_sehir').val();
                    ilce_id=$('#select_ilce').val();

                    if (ulke_id > 0){
                        $.ajax({
                            type: 'GET',
                            url: site_url+"/index.php",
                            data: 'islem=getNamazVakitleri&ulke_id='+ulke_id+'&sehir_id='+sehir_id+'&ilce_id='+ilce_id,
                            dataType: 'json',
                            success: function( data ) {

                                if (data[0].error !== undefined){

                                    // Ilce Yok...
                                    //alert(data[0].error);
                                    $('#uyari_p').empty().text('Namaz vakitlerine ulaşılamadı. Lütfen tekrar deneyiniz.');
                                    $('#uyari_p').show();
                                    $('#gonder_button').hide();

                                }else{
                                    var html_code ='<table id="namaz_vakitleri">';
                                    html_code +='<tr><th>Tarih</th><th>İmsak</th><th>Güneş</th><th>Öğle</th><th>İkindi</th><th>Akşam</th><th>Yatsı</th><th>Kıble</th></tr>';
                                    $.each(data, function(i, item) {

                                        html_code +='<tr><td>'+data[i].Tarih+'</td><td>'+data[i].Imsak+'</td><td>'+data[i].Gunes+'</td><td>'+data[i].Ogle+'</td><td>'+data[i].Ikindi+'</td><td>'+data[i].Aksam+'</td><td>'+data[i].Yatsi+'</td><td>'+data[i].Kible+'</td>';
                                    });

                                    $( '#inside_nmz_table' ).empty();
                                    $(html_code).appendTo( '#inside_nmz_table' );
                                    $( '#inside_nmz_table' ).show();
                                }
                            }
                        });
                    }

                });


            });

            function linkYaz(site_url,ulke_id,sehir_id,ilce_id){

                $('#link_a').attr('href','#');
                $('#link_a').html('#');

                if(sehir_id=='' && ilce_id==''){
                    href_a=site_url+'/index.php?islem=getNamazVakitleri&ulke_id='+ulke_id;
                    text_a=site_url+'/index.php?islem=getNamazVakitleri&ulke_id=<span class="red">'+ulke_id+'</span>';        
                }else if(sehir_id==''){
                    href_a=site_url+'/index.php?islem=getNamazVakitleri&ulke_id='+ulke_id;
                    text_a=site_url+'/index.php?islem=getNamazVakitleri&ulke_id=<span class="red">'+ulke_id+'</span>';        
                }else if(ilce_id==''){
                    href_a=site_url+'/index.php?islem=getNamazVakitleri&ulke_id='+ulke_id+'&sehir_id='+sehir_id;
                    text_a=site_url+'/index.php?islem=getNamazVakitleri&ulke_id=<span class="red">'+ulke_id+'</span>&sehir_id=<span class="green">'+sehir_id+'</span>';        
                }else{
                    href_a=site_url+'/index.php?islem=getNamazVakitleri&ulke_id='+ulke_id+'&sehir_id='+sehir_id+'&ilce_id='+ilce_id;
                    text_a=site_url+'/index.php?islem=getNamazVakitleri&ulke_id=<span class="red">'+ulke_id+'</span>&sehir_id=<span class="green">'+sehir_id+'</span>&ilce_id=<span class="orange">'+ilce_id+'</span>';        
                }

                $('#link_a').attr('href',href_a);
                $('#link_a').html(text_a);
                $('#link_p').show();
            }

            function linkKapat(){

                $('#link_p').hide();
                $('#link_a').attr('href','#');
                $('#link_a').html('#');
            }

        </script>
    </head>
    <body style="text-align: center;margin: 40px;font-family: monospace;">
        <h1 style="font-size: 40px;">Ahmeti Namaz Vakitleri API</h1>

        <p>Detaylı Bilgi: <a href="http://ahmeti.net/ahmeti-namaz-vakitleri-api-get-metodu-ile/">Ahmeti Namaz Vakitleri API (GET Metodu ile)</a></p>

        <table style="width: 250px; margin: 0 auto">
            <tr>
                <td style="width: 90px;text-align: right">Ülke Seç:</td>
                <td>
                    <select name="ulke" id="select_ulke" style="width: 150px">
                        <option>Seçiniz...</option>
                        <?php
                        $ulkeler=ulkeler_array();
                        foreach ($ulkeler as $ulke_id=>$ulke_adi) {
                            echo '<option value="'.$ulke_id.'">'.$ulke_adi.'</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr style="display: none" id="select_sehir_tr">
                <td style="width: 90px;text-align: right">Şehir Seç:</td>
                <td>
                    <select name="sehir" id="select_sehir" style="width: 150px;">
                        <option>Seçiniz...</option>
                    </select>
                </td>
            </tr>
            <tr style="display: none" id="select_ilce_tr">
                <td style="width: 90px;text-align: right">İlçe Seç:</td>
                <td>
                    <select name="ilce" id="select_ilce" style="width: 150px;">
                        <option>Seçiniz...</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button id="gonder_button" style="width: 100%;display: none">GÖSTER</button>
                </td>
            </tr>
        </table>
        <p id="uyari_p" style="color: red;display: none">Bu ülkeye ait ilçe bulunmamaktadır. Şehire göre sorgulama yapabilirsiniz.</p>
        <p id="link_p" style="display: none;margin-top: 20px">
            <span style="display: block;font-size: 22px;font-weight: bold">JSON Bağlantısı</span>
            <a target="_blank" href="#" id="link_a">#</a>
        </p>
        <div id="inside_nmz_table" style="display: none"></div>
    </body>
    </html>
<?php
}


function ulkeler_array()
{
    return array(
    '33'=>'ABD',
    '166'=>'AFGANISTAN',
    '13'=>'ALMANYA',
    '17'=>'ANDORRA',
    '140'=>'ANGOLA',
    '125'=>'ANGUILLA',
    '90'=>'ANTIGUA VE BARBUDA',
    '199'=>'ARJANTIN',
    '25'=>'ARNAVUTLUK',
    '153'=>'ARUBA',
    '59'=>'AVUSTRALYA',
    '35'=>'AVUSTURYA',
    '5'=>'AZERBAYCAN',
    '54'=>'BAHAMALAR',
    '132'=>'BAHREYN',
    '177'=>'BANGLADES',
    '188'=>'BARBADOS',
    '208'=>'BELARUS',
    '11'=>'BELCIKA',
    '182'=>'BELIZE',
    '181'=>'BENIN',
    '51'=>'BERMUDA',
    '93'=>'BIRLESIK ARAP EMIRLIGI',
    '83'=>'BOLIVYA',
    '9'=>'BOSNA HERSEK',
    '167'=>'BOTSVANA',
    '146'=>'BREZILYA',
    '97'=>'BRUNEI',
    '44'=>'BULGARISTAN',
    '91'=>'BURKINA FASO',
    '154'=>'BURMA (MYANMAR)',
    '65'=>'BURUNDI',
    '155'=>'BUTAN',
    '156'=>'CAD',
    '43'=>'CECENISTAN',
    '16'=>'CEK CUMHURIYETI',
    '86'=>'CEZAYIR',
    '160'=>'CIBUTI',
    '61'=>'CIN',
    '26'=>'DANIMARKA',
    '180'=>'DEMOKRATIK KONGO CUMHURIYETI',
    '176'=>'DOGU TIMOR',
    '123'=>'DOMINIK',
    '72'=>'DOMINIK CUMHURIYETI',
    '139'=>'EKVATOR',
    '63'=>'EKVATOR GINESI',
    '165'=>'EL SALVADOR',
    '117'=>'ENDONEZYA',
    '175'=>'ERITRE',
    '104'=>'ERMENISTAN',
    '6'=>'ESTONYA',
    '95'=>'ETYOPYA',
    '145'=>'FAS',
    '197'=>'FIJI',
    '120'=>'FILDISI SAHILI',
    '126'=>'FILIPINLER',
    '204'=>'FILISTIN',
    '41'=>'FINLANDIYA',
    '21'=>'FRANSA',
    '79'=>'GABON',
    '109'=>'GAMBIYA',
    '143'=>'GANA',
    '111'=>'GINE',
    '58'=>'GRANADA',
    '48'=>'GRONLAND',
    '171'=>'GUADELOPE',
    '169'=>'GUAM ADASI',
    '99'=>'GUATEMALA',
    '67'=>'GUNEY AFRIKA',
    '128'=>'GUNEY KORE',
    '62'=>'GURCISTAN',
    '82'=>'GUYANA',
    '70'=>'HAITI',
    '187'=>'HINDISTAN',
    '30'=>'HIRVATISTAN',
    '4'=>'HOLLANDA',
    '66'=>'HOLLANDA ANTILLERI',
    '105'=>'HONDURAS',
    '113'=>'HONG KONG',
    '15'=>'INGILTERE',
    '124'=>'IRAK',
    '202'=>'IRAN',
    '32'=>'IRLANDA',
    '23'=>'ISPANYA',
    '205'=>'ISRAIL',
    '12'=>'ISVEC',
    '49'=>'ISVICRE',
    '8'=>'ITALYA',
    '122'=>'IZLANDA',
    '119'=>'JAMAIKA',
    '116'=>'JAPONYA',
    '161'=>'KAMBOCYA',
    '184'=>'KAMERUN',
    '52'=>'KANADA',
    '34'=>'KARADAG',
    '94'=>'KATAR',
    '92'=>'KAZAKISTAN',
    '114'=>'KENYA',
    '168'=>'KIRGIZISTAN',
    '57'=>'KOLOMBIYA',
    '88'=>'KOMORLAR',
    '18'=>'KOSOVA',
    '162'=>'KOSTARIKA',
    '209'=>'KUBA',
    '206'=>'KUDUS',
    '133'=>'KUVEYT',
    '1'=>'KUZEY KIBRIS',
    '142'=>'KUZEY KORE',
    '134'=>'LAOS',
    '174'=>'LESOTO',
    '20'=>'LETONYA',
    '73'=>'LIBERYA',
    '203'=>'LIBYA',
    '38'=>'LIECHTENSTEIN',
    '47'=>'LITVANYA',
    '42'=>'LUBNAN',
    '31'=>'LUKSEMBURG',
    '7'=>'MACARISTAN',
    '98'=>'MADAGASKAR',
    '100'=>'MAKAO',
    '28'=>'MAKEDONYA',
    '55'=>'MALAVI',
    '103'=>'MALDIVLER',
    '107'=>'MALEZYA',
    '152'=>'MALI',
    '24'=>'MALTA',
    '87'=>'MARTINIK',
    '164'=>'MAURITIUS ADASI',
    '157'=>'MAYOTTE',
    '53'=>'MEKSIKA',
    '85'=>'MIKRONEZYA',
    '189'=>'MISIR',
    '60'=>'MOGOLISTAN',
    '46'=>'MOLDAVYA',
    '3'=>'MONAKO',
    '147'=>'MONTSERRAT (U.K.)',
    '106'=>'MORITANYA',
    '151'=>'MOZAMBIK',
    '196'=>'NAMBIYA',
    '76'=>'NEPAL',
    '84'=>'NIJER',
    '127'=>'NIJERYA',
    '141'=>'NIKARAGUA',
    '178'=>'NIUE',
    '36'=>'NORVEC',
    '80'=>'ORTA AFRIKA CUMHURIYETI',
    '131'=>'OZBEKISTAN',
    '77'=>'PAKISTAN',
    '149'=>'PALAU',
    '89'=>'PANAMA',
    '185'=>'PAPUA YENI GINE',
    '194'=>'PARAGUAY',
    '69'=>'PERU',
    '183'=>'PITCAIRN ADASI',
    '39'=>'POLONYA',
    '45'=>'PORTEKIZ',
    '68'=>'PORTO RIKO',
    '112'=>'REUNION',
    '37'=>'ROMANYA',
    '81'=>'RUANDA',
    '207'=>'RUSYA',
    '198'=>'SAMOA',
    '102'=>'SENEGAL',
    '138'=>'SEYSEL ADALARI',
    '200'=>'SILI',
    '179'=>'SINGAPUR',
    '27'=>'SIRBISTAN',
    '14'=>'SLOVAKYA',
    '19'=>'SLOVENYA',
    '150'=>'SOMALI',
    '74'=>'SRI LANKA',
    '129'=>'SUDAN',
    '172'=>'SURINAM',
    '191'=>'SURIYE',
    '64'=>'SUUDI ARABISTAN',
    '163'=>'SVALBARD',
    '170'=>'SVAZILAND',
    '101'=>'TACIKISTAN',
    '110'=>'TANZANYA',
    '137'=>'TAYLAND',
    '108'=>'TAYVAN',
    '71'=>'TOGO',
    '130'=>'TONGA',
    '96'=>'TRINIDAT VE TOBAGO',
    '118'=>'TUNUS',
    '2'=>'TURKIYE',
    '159'=>'TURKMENISTAN',
    '75'=>'UGANDA',
    '40'=>'UKRAYNA',
    '29'=>'UKRAYNA-KIRIM',
    '173'=>'UMMAN',
    '192'=>'URDUN',
    '201'=>'URUGUAY',
    '56'=>'VANUATU',
    '10'=>'VATIKAN',
    '186'=>'VENEZUELA',
    '135'=>'VIETNAM',
    '148'=>'YEMEN',
    '115'=>'YENI KALEDONYA',
    '193'=>'YENI ZELLANDA',
    '144'=>'YESIL BURUN',
    '22'=>'YUNANISTAN',
    '158'=>'ZAMBIYA',
    '136'=>'ZIMBABVE');    
}


function curl_file_get_contents($url)
{
 $curl = curl_init();
 $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
 
 curl_setopt($curl,CURLOPT_URL,$url);
 curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
 curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,5);
 
 curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
 curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
 curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
 curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
 curl_setopt($curl, CURLOPT_TIMEOUT, 10);
 
 $contents = curl_exec($curl);
 curl_close($curl);
 return $contents;
}


function curl_post($url,$data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);    
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    $page = curl_exec($ch);
    curl_close($ch);
    return $page;
}


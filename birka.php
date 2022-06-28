<?php
include_once 'barcode.php';

//создание элементов инфоблока 
AddEventHandler("iblock", "OnAfterIBlockElementAdd", Array("BirkaClass", "OnAfterIBlockElementAddHandler2"));
//обновление элементов инфоблока 
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", Array("BirkaClass", "OnAfterIBlockElementUpdateHandler2"));

//для цены
AddEventHandler("catalog", "OnPriceAdd", Array("BirkaClass","OnAfterIBlockElementHandler3") );
AddEventHandler("catalog", "OnPriceUpdate", Array("BirkaClass","OnAfterIBlockElementHandler3") );
//AddEventHandler("catalog", "OnProductUpdate", "IBlockElementAfterSaveHandler");

class BirkaClass
{
	public static $iBlockID = 14;

    function OnAfterIBlockElementAddHandler2(&$arFields)
    {
        if ( ($arFields["IBLOCK_ID"] == self::$iBlockID) && ($arFields["ACTIVE"] == "Y") ) { //Создан активный элемент в инфоблоке с id=14
			self::generate_birka($arFields);
        }
    }
    function OnAfterIBlockElementUpdateHandler2(&$arFields)
    {
         if ( ($arFields["IBLOCK_ID"] == self::$iBlockID) && ($arFields["ACTIVE"] == "Y") ) { //Изменен активный элемент в инфоблоке с id=14
			self::generate_birka($arFields);
        }
    }

    function OnAfterIBlockElementHandler3($priceID, &$arFields)
    {
    	$oElement = CIBlockElement::GetByID($arFields["PRODUCT_ID"]);
    	if ($fields = $oElement->GetNext()) {
    		self::generate_birka($fields);
    	}
    }

	function generate_birka($arFields) {

			if(CModule::IncludeModule('iblock'))
			{
				//получаем элемент
				$oElement = CIBlockElement::GetByID($arFields["ID"]);
				//получаем свойства элемента
				if($objElement = $oElement->GetNextElement())
				{
					$props = $objElement->GetProperties();
					$arFields['MY_PROPERTIES'] = $props;
				}

				$arFields['IBLOCK_SECTION'] = [];
				$groups = CIBlockElement::GetElementGroups($arFields["ID"], true);
				while($group = $groups->Fetch()) {
					$arFields['IBLOCK_SECTION'][] = $group["ID"];
				}

				//$newName = '';
				// наполнение массива для бирки
				$birka = array();
				if ( in_array(154, $arFields['IBLOCK_SECTION']) || in_array(225, $arFields['IBLOCK_SECTION']) ) {//Ювелирка Посуда
					$upName = self::my_mb_ucfirst(trim($arFields['MY_PROPERTIES']["TIP_IZDELIYA"]["VALUE"]));
					$birka['text'] = $upName;
					//добавим к названию свойства
					$metal = $arFields['MY_PROPERTIES']["METALL"]["VALUE"];
					if (strlen($metal) > 0) {
						$birka['metal'] = $metal;
						$birka['text'] .= ", ".$metal;
					}
					$proba = $arFields['MY_PROPERTIES']["PROBA"]["VALUE"];
					if (strlen($proba) > 0) {
						$birka['proba'] = $proba;
						$birka['text'] .= ", ".$proba.' пробы';
					}
					$oves = $arFields['MY_PROPERTIES']["OBSHCHIY_VES"]["VALUE"];
					if (strlen($oves) > 0) {
						$oves = floatval($oves);
						$birka['text'] .= ', общий вес '.$oves.' г';
						$birka['oves'] = $oves;
					}
					$chves = $arFields['MY_PROPERTIES']["CHISTYY_VES"]["VALUE"];
					if (strlen($chves) > 0) {
						$chves = floatval($chves);
						$birka['text'] .= ', чистый вес '.$chves.' г';
						$birka['chves'] = $chves;
					}
					$dlinacm = $arFields['MY_PROPERTIES']["DLINA_SM"]["VALUE"];
					if (strlen($dlinacm) > 0) {
						$dlinacm = floatval($dlinacm);
						$birka['dlinacm'] = $dlinacm;
						$birka['text'] .= ', длина '.$dlinacm.' см';
					}
					$razmer = $arFields['MY_PROPERTIES']["RAZMER_MM"]["VALUE"];
					if (strlen($razmer) > 0) {
						$razmer = floatval($razmer);
						$birka['razmer'] = $razmer;
						$birka['text'] .= ', размер '.$razmer.' мм';
					}/*else {
						$birka['razmer'] = "-";
					}*/
					if ( in_array(154, $arFields['IBLOCK_SECTION']) ) {//Ювелирка
						//добавим новые свойства
						//$price = floatval($arFields['MY_PROPERTIES']['PRICE']['VALUE']);
						//if ( $price == 0) {
							$ar_price = CPrice::GetBasePrice($arFields['ID']);
							$price = floatval($ar_price["PRICE"]);
						//}
						if ( $price > 0) {
							//для бирки
							$birka['price'] = $price;
						}
						$ppg = $arFields['MY_PROPERTIES']["PRICE_TOTAL_PER_GR"]["VALUE"];
						if (strlen($ppg) > 0) {
							$birka['ppg'] = $ppg;
						}
						$opsvs = $arFields['MY_PROPERTIES']["OPISANIEVSTAVOK"]["VALUE"];
						if (strlen($opsvs) > 0) {
							$birka['text'] .= ', '.$opsvs;
							$birka['opsvs'] = $opsvs;
						}
						/*$vs = $arFields['MY_PROPERTIES']["VSTAVKA"]["VALUE"];
						if (strlen($vs) > 0) {
							$birka['text'] .= ', '.$vs;
							$birka['vs'] = $vs;
						}*/
						/*$vs1 = $arFields['MY_PROPERTIES']["VSTAVKI"]["VALUE"];
						if (strlen($vs1) > 0) {//Да
							$ovfl = explode( ",",$arFields['MY_PROPERTIES']["OPISANIEVSTAVOK"]["VALUE"]);
							$ovfl = $ovfl[0];
							//$birka['text'] .= ', вставки '.mb_strtolower($vs1);
							//$birka['vs1'] = $vs1;
							$birka['vs1'] = mb_strtolower($ovfl);
						}*/
						$opisanie = $arFields['MY_PROPERTIES']["OPISANIE"]["VALUE"];
						if (strlen($opisanie) > 0) {
							$birka['text'] .= ', '.$opisanie;
							$birka['opisanie'] = $opisanie;
						}
						
						$deform = $arFields['MY_PROPERTIES']["DEFORMATSIYA"]["VALUE"];
						if (strlen($deform) > 0) {
							$birka['text'] .= ', '.$deform;
							$birka['deform'] = $deform;
						}
						$article = $arFields['MY_PROPERTIES']["CML2_ARTICLE"]["VALUE"];
						if (strlen($article) > 0) {
							$birka['article'] = $article;
						}
						$uin = $arFields['MY_PROPERTIES']["UIN"]["VALUE"];
						if (strlen($uin) > 0) {
							$birka['uin'] = $uin;
						}
						$org = $arFields['MY_PROPERTIES']["ORGANIZATSIYA"]["VALUE"];
						if (strlen($org) > 0) {
							$birka['org'] = $org;
						}
					}


	
					//отрисовка бирки
					$im = @imagecreatefromjpeg(dirname(__FILE__)."/img/birka.jpg");
					
					$txt = $birka['text'];
					$txt =  mb_strimwidth($birka['text'], 0, 200, "", "UTF-8");
					$fontFile = dirname(__FILE__)."/fonts/arial.ttf";
					$fontSize = 12;
					$fontColor = imagecolorallocate($im, 0, 0, 0);
					$posX = 14;
					$posY = 68;
					$angle = 0;
					//organization
					imagettftext($im, $fontSize, $angle, 90, 25, $fontColor, $fontFile, wordwrap($birka['org'], 65, "\n", false));
					//описание
					$pts = imagettftext($im, $fontSize - 2, $angle, $posX, $posY, $fontColor, $fontFile, wordwrap($txt, 65, "\n", false));
					$endtexty = $pts[1];
					//б\у
					$pts1 = imagettftext($im, $fontSize, $angle, $posX, $pts[1]+12, $fontColor, $fontFile, wordwrap("Бывшее в употреблении", 65, "\n", false));
					//артикул
					$pts2 = imagettftext($im, $fontSize+1, $angle, $posX, 188, $fontColor, $fontFile, wordwrap($birka['article'], 65, "\n", false)); 
					//металл
					$pts3 = imagettftext($im, $fontSize, $angle, $posX+170, 218, $fontColor, $fontFile, wordwrap($birka['metal'], 65, "\n", false)); 
					//проба
					$pts4 = imagettftext($im, $fontSize, $angle, $posX+100, 248, $fontColor, $fontFile, wordwrap($birka['proba'], 65, "\n", false));
					//заполнено либо свойство длина либо свойство размер либо ни одно из них не заполнено
					if ( $birka['dlinacm'] > 0 ) {
						//длина подпись
						$pts51 = imagettftext($im, $fontSize, $angle, $posX+155, 250, $fontColor, $fontFile, wordwrap("ДЛИНА, см:", 65, "\n", false));
						//длина значение
						$pts52 = imagettftext($im, $fontSize, $angle, $posX+265, 248, $fontColor, $fontFile, wordwrap($birka['dlinacm'], 65, "\n", false));						
					}
					if ( $birka['razmer'] > 0 ) {
						//размер подпись
						$pts53 = imagettftext($im, $fontSize, $angle, $posX+155, 250, $fontColor, $fontFile, wordwrap("РАЗМЕР, мм", 65, "\n", false));
						//размер значение
						$pts54 = imagettftext($im, $fontSize, $angle, $posX+265, 248, $fontColor, $fontFile, wordwrap($birka['razmer'], 65, "\n", false));
					}
					if ( ( $birka['dlinacm'] <= 0 ) && ( $birka['razmer'] <= 0 ) ) {
						//размер подпись
						$pts55 = imagettftext($im, $fontSize, $angle, $posX+155, 250, $fontColor, $fontFile, wordwrap("РАЗМЕР, мм:", 65, "\n", false));
						//размер значение
						$pts56 = imagettftext($im, $fontSize, $angle, $posX+265, 248, $fontColor, $fontFile, wordwrap("-", 65, "\n", false));						
					}

					
					//вставки
					//$pts6 = imagettftext($im, $fontSize, $angle, $posX+90, 278, $fontColor, $fontFile, wordwrap($birka['vs1'], 45, "\n", false));
					$opstext = $arFields['MY_PROPERTIES']["OPISANIEVSTAVOK"]["VALUE"];
					if ( strlen($opstext) > 1 ) {
						$bpos = strpos($opstext, "брил");
						$dpos = strpos($opstext, "недраг");
						if ( ( $bpos !== false ) && ( $dpos !== false ) ) {
							$pts6 = imagettftext($im, $fontSize, $angle, $posX+90, 278, $fontColor, $fontFile, wordwrap("бриллиант/недраг. камень", 45, "\n", false));
						}elseif ( ( $bpos !== false ) && ( $dpos === false )) {
							$pts6 = imagettftext($im, $fontSize, $angle, $posX+130, 278, $fontColor, $fontFile, wordwrap("бриллиант", 45, "\n", false));
						}elseif ( ( $bpos === false ) && ( $dpos !== false )) {
							$pts6 = imagettftext($im, $fontSize, $angle, $posX+110, 278, $fontColor, $fontFile, wordwrap("недрагоценный камень", 45, "\n", false));
						}else {
							$pts6 = imagettftext($im, $fontSize, $angle, $posX+90, 278, $fontColor, $fontFile, wordwrap("-", 45, "\n", false));
						}
					}else {
						$pts6 = imagettftext($im, $fontSize, $angle, $posX+90, 278, $fontColor, $fontFile, wordwrap("-", 45, "\n", false));
					}
					
					
					//происхождение
					$pts7 = imagettftext($im, $fontSize, $angle, $posX+220, 308, $fontColor, $fontFile, wordwrap("Россия", 65, "\n", false));
					//масса изделия
					$dimensions = imagettfbbox($fontSize+1, $angle, $fontFile,number_format($birka['oves'],3,","," "));
					$textWidth = abs($dimensions[4] - $dimensions[0]);
					$x8 = imagesx($im) - $textWidth;
					$pts8 = imagettftext($im, $fontSize+1, $angle, $x8-24, 338, $fontColor, $fontFile, number_format($birka['oves'],3,","," "));
					//цена за гр
					$dimensions = imagettfbbox($fontSize+1, $angle, $fontFile, number_format($birka['ppg'],2,","," "));
					$textWidth = abs($dimensions[4] - $dimensions[0]);
					$x9 = imagesx($im) - $textWidth;
					$pts9 = imagettftext($im, $fontSize+1, $angle, $x9-24, 368, $fontColor, $fontFile, number_format($birka['ppg'],2,","," ") );
					//цена за изделие
					$dimensions = imagettfbbox($fontSize+3, $angle, $fontFile, number_format($price,2,","," "));
					$textWidth = abs($dimensions[4] - $dimensions[0]);
					$x10 = imagesx($im) - $textWidth;
					$pts10 = imagettftext($im, $fontSize+3, $angle, $x10-24, 398, $fontColor, $fontFile, number_format($price,2,","," ") );
					//УИН
					$pts11 = imagettftext($im, $fontSize, $angle, 130, 598, $fontColor, $fontFile, $birka['uin']);					
					

					if (strlen($birka['uin']) > 0) {
						// datamatrix
						$generator = new barcode_generator();
						/* Output directly to standard output. */
						//$generator->output_image($format, $symbology, $data, $options);
						$symbology = "dmtx";
						$data = $birka['uin'];
						$options = "";
						/* Create bitmap image. */
						$imageDM = $generator->render_image($symbology, $data, $options);
						$imageDMdimensions = getimagesize($imageDM);
						//imagepng($image);
						//imagedestroy($image);

						imagecopymerge($im, $imageDM, 130, 450, 0, 0, 64, 64, 100);
					}
					
					imagejpeg($im, dirname(__FILE__)."/img/birka_e.jpg");

					//Store in the database.
					$arFile = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/birka/img/birka_e.jpg");
					CIBlockElement::SetPropertyValueCode($arFields["ID"], "BIRKA_IMG", $arFile);
				}
			}	
	}
	
    function my_mb_ucfirst($str, $encoding='UTF-8')
    {
        $str = mb_ereg_replace('^[\ ]+', '', $str);
        $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).mb_substr($str, 1, mb_strlen($str), $encoding);
        return $str;
    }	
}
?>
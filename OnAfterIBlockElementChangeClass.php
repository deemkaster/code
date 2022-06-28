<?php
//Перехватываем создание элементов инфоблока 
AddEventHandler("iblock", "OnAfterIBlockElementAdd", Array("OnAfterIBlockElementChangeClass", "OnAfterIBlockElementAddHandler"));
//Перехватываем обновление элементов инфоблока 
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", Array("OnAfterIBlockElementChangeClass", "OnAfterIBlockElementUpdateHandler"));

class OnAfterIBlockElementChangeClass
{
	public static $disableHandler = false;
	public static $iBlockID = 14;
	
    function OnAfterIBlockElementAddHandler(&$arFields)
    {
        if ( ($arFields["IBLOCK_ID"] == self::$iBlockID) && ($arFields["ACTIVE"] == "Y") ) { //Создан активный элемент в инфоблоке с id=8
			self::update_name($arFields);
        }
    }
    function OnAfterIBlockElementUpdateHandler(&$arFields)
    {
         if ( ($arFields["IBLOCK_ID"] == self::$iBlockID) && ($arFields["ACTIVE"] == "Y") ) { //Изменен активный элемент в инфоблоке с id=8
         	self::$disableHandler = false;
			self::update_name($arFields);
        }
    }
	function update_name($arFields) {
			$output = '';
			if (self::$disableHandler) {
				$output .= 'Blocked by handler!';
				return;
			}
			if(CModule::IncludeModule('iblock'))
			{
				//получаем элемент
				$oElement = CIBlockElement::GetByID($arFields["ID"]);
				//получаем свойства элемента
				if($objElement = $oElement->GetNextElement())
				{
					$props = $objElement->GetProperties();
					// перекладываем свойства в arFields!
					$arFields['MY_PROPERTIES'] = $props;
					//$output .= print_r($props, true);
				}

				// обновляем название элемента и описание элемента
				$newName = '';
				$birka = array();
				if ( in_array(154, $arFields['IBLOCK_SECTION']) || in_array(225, $arFields['IBLOCK_SECTION']) ) {//Ювелирка Посуда
					$upName = self::my_mb_ucfirst(trim($arFields['MY_PROPERTIES']["TIP_IZDELIYA"]["VALUE"]));
					if ( strlen($upName) < 1 )
						$output .= "Пустой тип изделия!";
					$output .= $upName;
					$newName = $upName;
					$birka['text'] = $newName;
					//добавим к названию свойства
					if ( !in_array(225, $arFields['IBLOCK_SECTION']) ) { // не дублируем слово серебро в посуде
						$metal = $arFields['MY_PROPERTIES']["METALL"]["VALUE"];
						if (strlen($metal) > 0) {
							$newName .= ' '.$metal;
							$birka['metal'] = $metal;
							$birka['text'] .= ", ".$metal;
						}
					}else {
						$metal = $arFields['MY_PROPERTIES']["METALL"]["VALUE"];
						if (strlen($metal) > 0) {
							$birka['metal'] = $metal;
							$birka['text'] .= ", ".$metal;
						}						
					}
					$proba = $arFields['MY_PROPERTIES']["PROBA"]["VALUE"];
					if (strlen($proba) > 0) {
						$newName .= ' '.$proba;
						$birka['proba'] = $proba;
						$birka['text'] .= ", ".$proba.' пробы';
					}
					$oves = $arFields['MY_PROPERTIES']["OBSHCHIY_VES"]["VALUE"];
					if (strlen($oves) > 0) {
						//remove gramms
						$oves = floatval($oves);
						$newName .= ', общий вес - '.$oves.' г';
						$birka['text'] .= ', общий вес '.$oves.' г';
						$birka['oves'] = $oves;
					}
					$chves = $arFields['MY_PROPERTIES']["CHISTYY_VES"]["VALUE"];
					if (strlen($chves) > 0) {
						$chves = floatval($chves);
						$newName .= ', чистый вес - '.$chves.' г';
						$birka['text'] .= ', чистый вес '.$chves.' г';
						$birka['chves'] = $chves;
					}
					$dlinacm = $arFields['MY_PROPERTIES']["DLINA_SM"]["VALUE"];
					if (strlen($dlinacm) > 0) {
						$dlinacm = floatval($dlinacm);
						$newName .= ', '.$dlinacm.' см';
						$birka['text'] .= ', длина '.$dlinacm.' см';
					}
					$razmer = $arFields['MY_PROPERTIES']["RAZMER"]["VALUE"];
					if (strlen($razmer) > 0) {
						$newName .= ', р-р '.$razmer;
						$birka['razmer'] = $razmer;
					}else {
						$birka['razmer'] = "-";
					}
					if ( in_array(154, $arFields['IBLOCK_SECTION']) ) {//Ювелирка
						//добавим новые свойства
						$price = floatval($arFields['MY_PROPERTIES']['PRICE']['VALUE']);
						$output .= " property price =".$price."= ";
						if ( $price == 0) {
							$ar_price = CPrice::GetBasePrice($arFields['ID']);
							$price = floatval($ar_price["PRICE"]);
							$output .= " baseprice=".$price."= ";
						}
						if ( $price > 0) {
							$totalWeight = floatval($arFields['MY_PROPERTIES']['OBSHCHIY_VES']['VALUE']);
							$output .= " totalWeight ".$totalWeight." ";
							if ( $totalWeight > 0) {
								$arFields['MY_PROPERTIES']['PRICE_TOTAL_PER_GR']['NAME'] = "Цена за грамм общего веса";
								$arFields['MY_PROPERTIES']['PRICE_TOTAL_PER_GR']['VALUE'] = round( $price/$totalWeight, 2);
								$output .= " PRICE_TOTAL_PER_GR ".$arFields['MY_PROPERTIES']['PRICE_TOTAL_PER_GR']['VALUE']." ";
							}
							$cleanWeight = floatval($arFields['MY_PROPERTIES']['CHISTYY_VES']['VALUE']);
							$output .= " cleanWeight ".$cleanWeight." ";
							if ( $cleanWeight > 0) {
								$arFields['MY_PROPERTIES']['PRICE_CLEAN_PER_GR']['NAME'] = "Цена за грамм чистого веса";
								$arFields['MY_PROPERTIES']['PRICE_CLEAN_PER_GR']['VALUE'] = round( $price/$cleanWeight, 2);
								$output .= " PRICE_CLEAN_PER_GR ".$arFields['MY_PROPERTIES']['PRICE_CLEAN_PER_GR']['VALUE']." ";
							}
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
						$vs = $arFields['MY_PROPERTIES']["VSTAVKA"]["VALUE"];
						if (strlen($vs) > 0) {
							$birka['text'] .= ', '.$vs;
							$birka['vs'] = $vs;
						}
						$vs1 = $arFields['MY_PROPERTIES']["VSTAVKI"]["VALUE"];
						if (strlen($vs1) > 0) {
							$birka['text'] .= ', вставки '.mb_strtolower($vs1);
							$birka['vs1'] = $vs1;
						}
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
					}
					//отрисовка бирки
					$im = @imagecreatefromjpeg(dirname(__FILE__)."/birka/birka.jpg");
					$txt = $birka['text'];
					$fontFile = dirname(__FILE__)."/fonts/arial.ttf"; // CHANGE TO YOUR OWN!
					$fontSize = 12;
					$fontColor = imagecolorallocate($im, 0, 0, 0);
					$posX = 14;
					$posY = 68;
					$angle = 0;
					//описание
					$pts = imagettftext($im, $fontSize - 2, $angle, $posX, $posY, $fontColor, $fontFile, wordwrap($txt, 65, "\n", false));
					$endtexty = $pts[1];
					//б\у
					$pts1 = imagettftext($im, $fontSize, $angle, $posX, $pts[1]+16, $fontColor, $fontFile, wordwrap("Бывшее в употреблении", 65, "\n", false));
					//артикул
					$pts2 = imagettftext($im, $fontSize+1, $angle, $posX, 188, $fontColor, $fontFile, wordwrap($birka['article'], 65, "\n", false)); 
					//металл
					$pts3 = imagettftext($im, $fontSize, $angle, $posX+170, 218, $fontColor, $fontFile, wordwrap($birka['metal'], 65, "\n", false)); 
					//проба
					$pts4 = imagettftext($im, $fontSize, $angle, $posX+100, 248, $fontColor, $fontFile, wordwrap($birka['proba'], 65, "\n", false));
					//размер
					$pts5 = imagettftext($im, $fontSize, $angle, $posX+260, 248, $fontColor, $fontFile, wordwrap($birka['razmer'], 65, "\n", false));
					//вставки
					$pts6 = imagettftext($im, $fontSize, $angle, $posX+100, 278, $fontColor, $fontFile, wordwrap($birka['vs1'], 65, "\n", false));
					//происхождение
					$pts7 = imagettftext($im, $fontSize, $angle, $posX+200, 308, $fontColor, $fontFile, wordwrap("Россия", 65, "\n", false));
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
					
					// datamatrix
					include 'barcode.php';
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
					/*$output .= "image";
					$output .= print_r($imageDMdimensions, true);
					$output .= "endimage";*/
					imagecopymerge($im, $imageDM, 130, 450, 0, 0, 64, 64, 100);
					
					//Store in the filesystem.
					//if ( strlen($arFields['MY_PROPERTIES']["BIRKA_IMG"]["VALUE"]) < 1 )
					//{
						imagejpeg($im, dirname(__FILE__)."/birka/birka_e.jpg");
					//}
					//END отрисовка бирки
					//Store in the database.
					$arFile = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/birka/birka_e.jpg");
					CIBlockElement::SetPropertyValueCode($arFields["ID"], "BIRKA_IMG", $arFile);
				}
				if ( in_array(161, $arFields['IBLOCK_SECTION']) || in_array(189, $arFields['IBLOCK_SECTION']) || in_array(227, $arFields['IBLOCK_SECTION'])  ) {//Брендовые часы, Малая бытовая техника, стройматериалы
					$upName = self::my_mb_ucfirst(trim($arFields['MY_PROPERTIES']["TIP_IZDELIYA"]["VALUE"]));
					if ( strlen($upName) < 1 )
						$output .= "Пустой тип изделия!";
					$output .= $upName;
					$newName = $upName;
					//добавим к названию свойства
					$brend = $arFields['MY_PROPERTIES']["BREND"]["VALUE"];
					if (strlen($brend) > 0) {
						$newName .= ' '.$brend;
					}
					$model = $arFields['MY_PROPERTIES']["MODEL"]["VALUE"];
					if (strlen($model) > 0) {
						$newName .= ' '.$model;
					}
				}
				if ( in_array(164, $arFields['IBLOCK_SECTION']) || in_array(165, $arFields['IBLOCK_SECTION']) || 
				in_array(172, $arFields['IBLOCK_SECTION']) || in_array(173, $arFields['IBLOCK_SECTION']) ||	
				in_array(174, $arFields['IBLOCK_SECTION'])) {//жакет, жилет, манто, пальто, полупальто
					$upName = self::my_mb_ucfirst(trim($arFields['MY_PROPERTIES']["TIP_IZDELIYA"]["VALUE"]));
					if ( strlen($upName) < 1 )
						$output .= "Пустой тип изделия!";
					$output .= $upName;
					$newName = $upName;
					//добавим к названию свойства
					$mekh = $arFields['MY_PROPERTIES']["OSNOVNOY_MEKH"]["VALUE"];
					if (strlen($mekh) > 0) {
						$newName .= ', '.$mekh;
					}
					$razmer = $arFields['MY_PROPERTIES']["RAZMER_1"]["VALUE"];
					if (strlen($razmer) > 0) {
						$newName .= ', размер '.$razmer;
					}
				}
				if ( in_array(166, $arFields['IBLOCK_SECTION']) || in_array(167, $arFields['IBLOCK_SECTION']) || 
				in_array(168, $arFields['IBLOCK_SECTION']) || in_array(169, $arFields['IBLOCK_SECTION']) ||	
				in_array(170, $arFields['IBLOCK_SECTION']) || in_array(171, $arFields['IBLOCK_SECTION']) ) {//кожаная куртка, кожаный жилет, кожаный кошелек, кожаный плащ, кожаный пуховик, кожаный чемодан
					$upName = self::my_mb_ucfirst(trim($arFields['MY_PROPERTIES']["TIP_IZDELIYA"]["VALUE"]));
					if ( strlen($upName) < 1 )
						$output .= "Пустой тип изделия!";
					$output .= $upName;
					$newName = $upName;
					//добавим к названию свойства
					$razmer = $arFields['MY_PROPERTIES']["RAZMER_1"]["VALUE"];
					if (strlen($razmer) > 0) {
						$newName .= ', размер '.$razmer;
					}
					$dlinaism = $arFields['MY_PROPERTIES']["DLINA_IZDELIYA_SM"]["VALUE"];
					if (strlen($dlinaism) > 0) {
						$newName .= ', длина '.$dlinaism;
					}
				}
				if ( in_array(248, $arFields['IBLOCK_SECTION']) ) {//Технические средства
					if ( in_array(260, $arFields['IBLOCK_SECTION']) ) {//Андроид
						//добавим к названию свойства
						$brend = $arFields['MY_PROPERTIES']["BREND"]["VALUE"];
						if (strlen($brend) > 0) {
							$upName = self::my_mb_ucfirst(trim($brend));
							$output .= $upName;
							$newName = $upName;
						}else {
							$output .= "Пустой брэнд!";
						}
						$model = $arFields['MY_PROPERTIES']["MODEL"]["VALUE"];
						if (strlen($model) > 0) {
							$newName .= ' '.$model;
						}else{
							$output .= "Пустая модель!";
						}
					}else{
						$upName = self::my_mb_ucfirst(trim($arFields['MY_PROPERTIES']["TIP_IZDELIYA"]["VALUE"]));
						if ( strlen($upName) < 1 )
							$output .= "Пустой тип изделия!";
						$output .= $upName;
						$newName = $upName;
						//добавим к названию свойства
						$brend = $arFields['MY_PROPERTIES']["BREND"]["VALUE"];
						if (strlen($brend) > 0) {
							$newName .= ' '.$brend;
						}
						$model = $arFields['MY_PROPERTIES']["MODEL"]["VALUE"];
						if (strlen($model) > 0) {
							$newName .= ' '.$model;
						}
					}
				}
				$output .= $newName;
				//обновляем название элемента
				if (strlen(trim($newName)) > 1) {
					$arLoadProductArray = Array(
					  "ACTIVE" => "Y",
					  'TIMESTAMP_X' => FALSE,
					  'NAME' => $newName
					);
					self::$disableHandler = true;
					$el = new CIBlockElement;
					if ( !$el->Update($arFields["ID"], $arLoadProductArray))
						$output .= 'Update Name Error: '.$el->LAST_ERROR; 
				}
				//обновляем новые свойства
				if ( $arFields['MY_PROPERTIES']['PRICE_TOTAL_PER_GR']['VALUE'] > 0 ) {
					//проверяем наличие свойства
					$properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>self::$iBlockID, "CODE" => "PRICE_TOTAL_PER_GR"));
					$count1 = $properties->SelectedRowsCount();
					$output .= ' count '.$count1. ' ';
					if ($count1 < 1) {// создаем свойство
						$arLoadPropertyArray = Array(
							"NAME" => $arFields['MY_PROPERTIES']['PRICE_TOTAL_PER_GR']['NAME'],
							"ACTIVE" => "Y",
							"SORT" => 500,
							"CODE" => "PRICE_TOTAL_PER_GR",
							"PROPERTY_TYPE" => "N",//число
							//"VALUE" => $arFields['MY_PROPERTIES']['PRICE_TOTAL_PER_GR']['VALUE'],
							"IBLOCK_ID" => self::$iBlockID
						);
						$ibp = new CIBlockProperty;
						if ( $PropID = $ibp->Add($arLoadPropertyArray) ) {
							$output .= 'propid '.$PropID.' ';
						}else{
							$output .= 'Create prop Error: '.$ibp->LAST_ERROR.' ';
						}	
					}
					//обновляем значение свойства
					$el = new CIBlockElement;
					$arLoadPropArray = Array("PRICE_TOTAL_PER_GR"=>$arFields['MY_PROPERTIES']['PRICE_TOTAL_PER_GR']['VALUE']);
					$el->SetPropertyValuesEx($arFields["ID"], self::$iBlockID, $arLoadPropArray);
				}
				if ( $arFields['MY_PROPERTIES']['PRICE_CLEAN_PER_GR']['VALUE'] > 0 ) {
					//проверяем наличие свойства
					$properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>self::$iBlockID, "CODE" => "PRICE_CLEAN_PER_GR"));
					$count1 = $properties->SelectedRowsCount();
					$output .= ' count '.$count1. ' ';
					if ($count1 < 1) {// создаем свойство
						$arLoadPropertyArray = Array(
							"NAME" => $arFields['MY_PROPERTIES']['PRICE_CLEAN_PER_GR']['NAME'],
							"ACTIVE" => "Y",
							"SORT" => 500,
							"CODE" => "PRICE_CLEAN_PER_GR",
							"PROPERTY_TYPE" => "N",//число
							//"VALUE" => $arFields['MY_PROPERTIES']['PRICE_CLEAN_PER_GR']['VALUE'],
							"IBLOCK_ID" => self::$iBlockID
						);
						$ibp = new CIBlockProperty;
						if ( $PropID = $ibp->Add($arLoadPropertyArray) ) {
							$output .= 'propid '.$PropID.' ';
						}else{
							$output .= 'Create prop Error: '.$ibp->LAST_ERROR.' ';
						}	
					}
					//обновляем значение свойства
					$el = new CIBlockElement;
					$arLoadPropArray = Array("PRICE_CLEAN_PER_GR"=>$arFields['MY_PROPERTIES']['PRICE_CLEAN_PER_GR']['VALUE']);
					$el->SetPropertyValuesEx($arFields["ID"], self::$iBlockID, $arLoadPropArray);
				}
			}
			
			// вывод сообщений в лог
			$output .= print_r($arFields, true);
			if (!file_exists(dirname(__FILE__).'/logs_change')) {
				mkdir(dirname(__FILE__).'/logs_change', 0777, true);
			}
			file_put_contents(dirname(__FILE__).'/logs_change/element_'.$arFields['ID'].'.txt', $output);		
			file_put_contents(dirname(__FILE__).'/logs_change/birka_'.$arFields['ID'].'.txt', print_r($birka, true));		
			//file_put_contents(dirname(__FILE__).'/logs_change/pts_'.$arFields['ID'].'.txt', print_r($pts, true));		
	}
	function check_name($string) {
		//$parts = explode("\s", $string);
		//$lenght = strlen($string);
		if(strpos(trim($string), ' ') !== false)
		{
			// multiple words
			return false;
		}
		else
		{
			// one word
			return true;
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
<?
/*
You can place here your functions and event handlers

AddEventHandler("module", "EventName", "FunctionName");
function FunctionName(params)
{
	//code
}
*/
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\Type\DateTime;
// id highload-инфоблока
const MY_HL_BLOCK_ID = 4;
//подключаем модуль highloadblock
CModule::IncludeModule('highloadblock');
CModule::IncludeModule("iblock");
CModule::IncludeModule("catalog");
//getDollarRate();

function getDollarRate(){
	// Получаем текущие курсы валют в rss-формате с сайта www.cbr.ru 
	$content = get_content(); 
	// Разбираем содержимое, при помощи регулярных выражений 
	$pattern = "#<Valute ID=\"([^\"]+)[^>]+>[^>]+>([^<]+)[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)[^>]+>[^>]+>([^<]+)#i"; 
	preg_match_all($pattern, $content, $out, PREG_SET_ORDER); 
	$dollar = ""; 
	foreach($out as $cur) 
	{ 
		if($cur[2] == 840) $dollar = str_replace(",",".",$cur[4]); 
	} 
	echo "Доллар - ".$dollar."<br>"; 
	 
	$entity_data_class = GetEntityDataClass(MY_HL_BLOCK_ID);
	$rsData = $entity_data_class::getList(array(
	  'select' => array('ID','UF_RATE','UF_DATETIME'),
	  'order' => array('ID' => 'DESC'),
	  'limit' => '1',
	));
	if ($arItem = $rsData->Fetch()) {
		//print_r($arItem);
		//echo abs($arItem['UF_RATE']/$dollar - 1);
		if( ($dollar > 30) && ($arItem['UF_RATE'] != $dollar) && ( abs($arItem['UF_RATE']/$dollar - 1) <= .05 ) ) {
			//добавление
			$dateTime = new \Bitrix\Main\Type\DateTime();
			$result = $entity_data_class::add(array(
				  'UF_RATE'         => $dollar,
				  'UF_DATETIME'         => $dateTime,
			   ));

			//запуск пересчета
			//getPrices(30,5);
			setPrices(30,1,5,$dollar);
		}
	}

	//echo $result;
	return "getDollarRate();";
}
function getPrices($IblockID,$PriceID){
	//$arSelect = Array("NAME","ID");
	$arSelect = Array("NAME","ID",'CATALOG_GROUP_'.$PriceID);
	$arFilter = Array("IBLOCK_ID"=>IntVal($IblockID),"ACTIVE"=>"Y");
	$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
	$i=0;
	while(($ob = $res->GetNextElement()))
	{
		$i++;
		$arFields = $ob->GetFields();
		echo "<br>".$i.") -->".$arFields['NAME'];

		//$arPrice = CPrice::GetByID($arFields['ID']);
		$rsPrices = CPrice::GetList(array(),array('PRODUCT_ID' => $arFields['ID'],'CATALOG_GROUP_ID' => $PriceID));
		if ($arPrice = $rsPrices->Fetch())
		{
		   // далее по тексту
			echo " цена: ".$arPrice["PRICE"];
			//print_r($arPrice);
		}

	}
}
function setPrices($IblockID, $BasePriceID = 1, $PriceID,$ml){
	$arSelect = Array("NAME","ID",'CATALOG_GROUP_'.$PriceID);
	$arFilter = Array("IBLOCK_ID"=>IntVal($IblockID),"ACTIVE"=>"Y");
	$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
	$i=0;
	while(($ob = $res->GetNextElement()))
	{
		$i++;
		$arFields = $ob->GetFields();
		echo "<br>".$i.") -->".$arFields['NAME'];

		$rPrice = 0;
		$rsPrices = CPrice::GetList(array(),array('PRODUCT_ID' => $arFields['ID'],'CATALOG_GROUP_ID' => $PriceID));
		if ($arPrice = $rsPrices->Fetch())
		{
			echo " цена(USD): ".$arPrice["PRICE"];
			$rPrice = round($ml*$arPrice["PRICE"]*1.06,2);
			echo " цена(RUB): ".$rPrice;
		}
		$arFieldsB = Array(
			"PRODUCT_ID" => $arFields['ID'],
			"CATALOG_GROUP_ID" => $BasePriceID,
			"PRICE" => $rPrice,
			"CURRENCY" => "RUB",
		);
		$rsPricesB = CPrice::GetList(array(),array('PRODUCT_ID' => $arFields['ID'],'CATALOG_GROUP_ID' => $BasePriceID));
		if ($arPriceB = $rsPricesB->Fetch())
		{
			$result = CPrice::Update($arPriceB['ID'], $arFieldsB);
		}else{
			$result = CPrice::Add($arFieldsB);
		}
		echo ' id= '.$result;
	}
	return $result;
}
function get_content() 
{ 
	// Формируем сегодняшнюю дату 
	$date = date("d/m/Y"); 
	// Формируем ссылку 
	$link = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=$date"; 
	// Загружаем HTML-страницу 

	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $link);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	$contents = curl_exec($ch);

	if (curl_errno($ch)) {
		echo curl_error($ch);
		echo "\n<br />";
		$contents = '';
	} else {
		curl_close($ch);
	}

	if (!is_string($contents) || !strlen($contents)) {
		echo "Failed to get contents.";
		$contents = '';
	}

	return $contents;
}
//Напишем функцию получения экземпляра класса:
function GetEntityDataClass($HlBlockId) {
    if (empty($HlBlockId) || $HlBlockId < 1)
    {
        return false;
    }
    $hlblock = HLBT::getById($HlBlockId)->fetch();   
    $entity = HLBT::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();
    return $entity_data_class;
}

?>
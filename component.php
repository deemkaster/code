<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule("iblock");

// Проверка и инициализация входных параметров
if ( !isset($arParams["SECTION_ID"]) ) $arParams["SECTION_ID"] = 0;
//echo $arParams["SECTION_ID"];

// Если нет валидного кеша (то есть нужно запросить
// данные и сделать валидный кеш)
if ($this->StartResultCache())
{
	$arResult = array();
	$arSectionKeys = array();
	$iBlockID = $arParams["IBLOCK_ID"];
	
	//get all sections
	$arSections = array();
	$arFilter = Array('IBLOCK_ID'=>$iBlockID, 'GLOBAL_ACTIVE'=>'Y','SECTION_ID' => $arParams["SECTION_ID"]);
	$db_list = CIBlockSection::GetList(Array("SORT" => "ASC"), $arFilter, true, array());

	while($ar_result = $db_list->GetNext()) 
	{
		$arResult["SECTIONS"][$ar_result['ID']] = Array( 'ID' => $ar_result['ID'], 'NAME' => $ar_result['NAME'], 'SORT' => $ar_result["SORT"] );
	}
	//get all elements
	$arFiles = array();
	$arFilter = Array("IBLOCK_ID"=>$iBlockID, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y", "SECTION_ID" => $arParams["SECTION_ID"], "INCLUDE_SUBSECTIONS" => "Y", "SECTION_GLOBAL_ACTIVE" => "Y" );
	$arSelect = Array("ID", "IBLOCK_ID", "NAME", "DATE_ACTIVE_FROM", "IBLOCK_SECTION_ID", "PROPERTY_FILE");//IBLOCK_ID и ID IBLOCK_SECTION_ID обязательно должны быть указаны, см. описание arSelectFields выше
	$res = CIBlockElement::GetList(Array("SORT" => "ASC"), $arFilter, false, false, $arSelect);
	$k=0;
	while($ob = $res->GetNext()) {
		$fileProps = CIBlockElement::GetProperty($iBlockID, $ob["ID"], array("sort" => "asc"), Array("CODE"=>"FILE"));
		$fileProps = $fileProps->Fetch();
		$arFile = CIBlockFormatProperties::GetDisplayValue($ob, $fileProps);
		$ob["FILE"] = $arFile;
		$cFile = $ob["IBLOCK_SECTION_ID"];

		if ( isset($arResult["SECTIONS"][$cFile]) ) {
			$arResult["SECTIONS"][$cFile]["ITEMS"][] = $ob;
		}
		unset($arFile);
		unset($arProps);
		$k++;
	}
	unset($arFilter);
	unset($arSelect);
	unset($arSections);
	$this->IncludeComponentTemplate();
}
// Установить заголовок страницы с помощью отложенной
// функции
// $APPLICATION->SetTitle($arResult["ID"]); 
?>
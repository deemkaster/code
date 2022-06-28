<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponentTemplate $this */
$arResult['PHOTO'] = [];
if($arResult['DETAIL_PICTURE']){
    $arResult['PHOTO'][] = $arResult['DETAIL_PICTURE']['ID'];
}
if($arResult['PROPERTIES']['MORE_PHOTO']['VALUE']){
  $arResult['PHOTO'] = array_merge($arResult['PHOTO'], $arResult['PROPERTIES']['MORE_PHOTO']['VALUE']);
}
//print_r($arResult['PROPERTIES']['MORE_PHOTO']);
$birka = array( 1 => $arResult['PROPERTIES']['BIRKA_IMG']["VALUE"]);
$arResult['PHOTO'] = array_insert_after($arResult['PHOTO'],0,$birka);
//print_r($birka);
//print_r($arResult['PHOTO']);
function array_insert_after( array $array, $key, array $new ) {
	$keys = array_keys( $array );
	$index = array_search( $key, $keys );
	$pos = false === $index ? count( $array ) : $index + 1;

	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}
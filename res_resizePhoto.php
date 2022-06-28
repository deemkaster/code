<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

function getPhotoArray($arData) {
   $arThumbPhoto = CFile::ResizeImageGet(
      $arData["ID"],
      Array('width'=>104, 'height'=>84),
      BX_RESIZE_IMAGE_EXACT,
      true, 
      Array()       
   );

   $arSmallPhoto = CFile::ResizeImageGet(
      $arData["ID"],
      Array('width'=>425, 'height'=>248),
      BX_RESIZE_IMAGE_PROPORTIONAL,
      true, 
      Array()
   );

   $arResult = Array(
      "THUMB" => Array(
         "SRC"      => $arThumbPhoto['src'],
         "HEIGHT"   => $arThumbPhoto['height'],
         "WIDTH"      => $arThumbPhoto['width'],
      ),
      "SMALL"   => Array(
         "SRC"      => $arSmallPhoto['src'],
         "HEIGHT"   => $arSmallPhoto['height'],
         "WIDTH"      => $arSmallPhoto['width'],
      )
   );

   return $arResult;
}

foreach($arResult["MORE_PHOTO"] as $key=>$iPhotoId) {
   $arResult["MORE_PHOTO"][$key] = array_merge(
      $arResult["MORE_PHOTO"][$key],
      getPhotoArray($iPhotoId)
   );
}

?>
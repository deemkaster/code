<?php
if (defined('ADMIN_SECTION')) $APPLICATION->SetAdditionalCSS('/css/admin.css');   // поправьте путь к файлу

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php");


function setLog($title, $value, $path)
{
    if (!empty($path))
    {
        $start = '--- '.$title.' ('. date('d.m.Y H:i') .') ---'.PHP_EOL;
        $end   = '---/ '.$title.' ('. date('d.m.Y H:i') .') ---'.PHP_EOL.PHP_EOL;
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . $path, print_r($start, true), FILE_APPEND);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . $path, print_r($value, true), FILE_APPEND);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . $path, print_r($end, true), FILE_APPEND);
    }
}

require_once(dirname(__FILE__) . "/include/defines.php");
require_once(dirname(__FILE__) . "/include/functions.php");
require_once(dirname(__FILE__) . "/include/eventHandlers.php");
require_once(dirname(__FILE__) . "/include/CStorage.php");
require_once(dirname(__FILE__) . "/include/BXHelper.php");
require_once(dirname(__FILE__) . "/include/CHLEntity.php");
require_once(dirname(__FILE__) . "/include/firebase2/firebaseLib.php");
require_once(dirname(__FILE__) . "/include/FireStoreConnect.php");
require_once(dirname(__FILE__) . "/include/FireStoreConnect_dept.php");
require_once(dirname(__FILE__) . "/include/FireStoreTicket.php");
require_once(dirname(__FILE__) . "/include/FireStoreCart.php");
require_once(dirname(__FILE__) . "/include/OrganizationCart.php");
require_once(dirname(__FILE__) . "/include/CustomOnSaleOrder.php");
require_once(dirname(__FILE__) . "/include/generateLinkToProduct.php");

//изменение названия элемента и также добавление новых свойств «Цена за грамм общего веса» и «Цена за грамм чистого веса»
require_once(dirname(__FILE__) . "/include/OnAfterIBlockElementChangeClass.php");

//добавление полей в почтовые шаблоны 
require_once(dirname(__FILE__) . "/include/OnBeforeEventAddClass.php");

//создание бирки
require_once(dirname(__FILE__) . "/include/birka/birka.php");

CModule::IncludeModule('catalog');
CModule::IncludeModule('sale');


// ============= Функции по работе с разделами ==============
// -------- Получение раздела по ID -----------
function getSectionById($section_id)
{
    $arResult = CIBlockSection::GetById($section_id);
    if ($arSection = $arResult->Fetch()) {
        return $arSection;
    }
}

// -------- Получение раздела по CODE -----------
function getSectionByCode($iblock_id, $elem_code)
{
    $arFilter = array('IBLOCK_ID' => $iblock_id, 'CODE' => $elem_code);
    $arResult = CIBlockSection::GetList(array('ID' => 'ASC'), $arFilter, false, array());
    if ($arElement = $arResult->Fetch()) {
        return $arElement;
    }
}

// -------- Получение раздела по URL -------------
function getSectionByUrl($iblock_id, $url)
{
    $arUrl = explode('/', $url);
    $sect_code = $arUrl[count($arUrl) - 2];
    $arResult = getSectionByCode($iblock_id, $sect_code);

    return $arResult;
}

// -------- Получение раздела по фильтру -----------
function getSectionList($filter, $select)
{
    $arValues = array();
    $arResult = CIBlockSection::GetList(array('ID' => 'ASC'), $filter, false, $select);

    while ($arSection = $arResult->Fetch()) {
        $arValues[] = $arSection;
    }

    return $arValues;
}

// --------- Иерархический вывод разделов ----------
function getSectionTreeList($filter, $select)
{
    $dbSection = CIBlockSection::GetList(
        Array(
            'LEFT_MARGIN' => 'ASC',
        ),
        array_merge(
            Array(
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y'
            ),
            is_array($filter) ? $filter : Array()
        ),
        false,
        array_merge(
            Array(
                'ID',
                'IBLOCK_SECTION_ID'
            ),
            is_array($select) ? $select : Array()
        )
    );

    while ($arSection = $dbSection->GetNext(true, false)) {

        $SID = $arSection['ID'];
        $PSID = (int)$arSection['IBLOCK_SECTION_ID'];

        $arLincs[$PSID]['CHILDS'][$SID] = $arSection;

        $arLincs[$SID] = &$arLincs[$PSID]['CHILDS'][$SID];
    }

    return array_shift($arLincs);
}

// ============= /Функции по работе с разделами ==============




// ============ Функции по работе с элементами ==============
//--------- Получение элемента по ID ---------
function getElementById($element_id)
{
    $arResult = CIBlockElement::GetById($element_id);

    if ($arElement = $arResult->Fetch())
    {
        return $arElement;
    }
}

//--------- Получение элемента по CODE ---------
function getElementByCode($iblock_id, $elem_code)
{
    $arFilter = array('IBLOCK_ID' => $iblock_id, 'CODE' => $elem_code);
    $arResult = CIBlockElement::GetList(array('ID' => 'ASC'), $arFilter, false, false, array());

    if ($arElement = $arResult->Fetch())
    {
        return $arElement;
    }
}

// -------- Получение раздела по URL -------------
function getElementByUrl($iblock_id, $url)
{
    $arUrl     = explode('/', $url);
    $elem_code = $arUrl[count($arUrl) - 2];
    $arResult  = getElementByCode($iblock_id, $elem_code);

    return $arResult;
}

// -------- Получение элемента по фильтру -----------
function getElementList($filter, $select)
{
    $arValues = array();
    $arResult = CIBlockElement::GetList(array('ID' => 'ASC'), $filter, false, false, $select);

    while ($arElement = $arResult->Fetch())
    {
        $arValues[] = $arElement;
    }

    return $arValues;
}

//---------- Получение списка тегов товара -----------
function getElementTagsById($id)
{
    $arValues = array();
    $arPropRs = CIBlockElement::GetPropertyValues(14, array('ID' => $id), false, array('ID' => array(463)));

    while ($arProp = $arPropRs->Fetch())
    {
        foreach ($arProp[463] as $key => $val)
        {
            $arValues[] = CIBlockPropertyEnum::GetByID($val);
        }
    }

    return $arValues;
}
//---------- /Получение списка тегов товара -----------
// ============ /Функции по работе с элементами ==============





// ============ Функции по работе со свойствами =============
// -------- Получение свойства по ID ---------
function getElementPropertyByID($iblock_id, $elem_id, $prop_id)
{

    $arElemProp   = array();
    $arPropResult = CIBlockElement::GetProperty($iblock_id, $elem_id, array(), Array("ID" => $prop_id));

    while ($arProp = $arPropResult->Fetch())
    {
        array_push($arElemProp, $arProp);
    }

    return $arElemProp;
}

// -------- Получение свойства по CODE ---------
function getElementPropertyByCode($iblock_id, $elem_id, $prop_code)
{

    $arElemProp   = array();
    $arPropResult = CIBlockElement::GetProperty($iblock_id, $elem_id, array(), Array("CODE" => $prop_code));

    while ($arProp = $arPropResult->Fetch())
    {
        array_push($arElemProp, $arProp);
    }

    unset($arPropResult);
    return $arElemProp;
}

// --------- Получение значений типа справочник,
/*
===== Массив для использования функцией можно получить, например, через функцию getElementPropertyByID() ====
*/

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock as HL;

function getHiloadEnum($arProp)
{

    /**
     * @var array Массив описывающий свойство типа справочник
     */
    $arHighloadProperty = $arProp;

    /**
     * @var string название таблицы справочника
     */
    $sTableName = $arHighloadProperty['USER_TYPE_SETTINGS']['TABLE_NAME'];

    /**
     * Работаем только при условии, что
     *    - модуль highloadblock подключен
     *    - в описании присутствует таблица
     *    - есть заполненные значения
     */
    if (Loader::IncludeModule('highloadblock') && !empty($sTableName) && !empty($arHighloadProperty["VALUE"]))
    {
        /**
         * @var array Описание Highload-блока
         */
        $hlblock = HL\HighloadBlockTable::getRow([
            'filter' =>
            [
                '=TABLE_NAME' => $sTableName
            ],
        ]);

        if ($hlblock)
        {
            /**
             * Магия highload-блоков компилируем сущность, чтобы мы смогли с ней работать
             *
             */
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entityClass = $entity->getDataClass();

            $arRecords = $entityClass::getList([
                'filter' =>
                [
                    //'UF_XML_ID' => $arHighloadProperty["VALUE"]
                ],
            ]);
            foreach ($arRecords as $record)
            {
                /**
                 * Тут любые преобразования с записью, полученной из таблицы.
                 * Я транслировал почти все напрямую.
                 *
                 * Нужно помнить, что например в UF_FILE возвращается ID файла,
                 * а не полный массив описывающий файл
                 */
                $arRecord =
                [
                    'ID'                  => $record['ID'],
                    'UF_NAME'             => $record['UF_NAME'],
                    'UF_SORT'             => $record['UF_SORT'],
                    'UF_XML_ID'           => $record['UF_XML_ID'],
                    'UF_LINK'             => $record['UF_LINK'],
                    'UF_DESCRIPTION'      => $record['UF_DESCRIPTION'],
                    'UF_FULL_DESCRIPTION' => $record['UF_FULL_DESCRIPTION'],
                    'UF_DEF'              => $record['UF_DEF'],
                    'UF_FILE'             => [],
                    '~UF_FILE'            => $record['UF_FILE'],
                ];

                /**
                 * Не очень быстрое решение - сколько записей в инфоблоке, столько файлов и получим
                 * Хорошо было бы вынести под код и там за 1 запрос все получить, а не плодить
                 * по дополнительному запросу на каждый файл
                 */
                if (!empty($arRecord['~UF_FILE']))
                {
                    $arRecord['UF_FILE'] = \CFile::getById($arRecord['~UF_FILE'])->fetch();
                }

                $arHighloadProperty['EXTRA_VALUE'][] = $arRecord;
            }
        }


        return $arHighloadProperty;
    }
}

// ============ /Функции по работе со свойствами =============


// ============= Дополнительные функции ===========
// -------- Удобный дебаг --------
function getDebug($str)
{
    global $USER;

    if ($USER->isAdmin())
    {
        echo "<pre>";
        if (gettype($str) == 'string')
        {
            echo $str;
        }
        else
        {
            print_r($str);
        }
        echo "</pre>";
    }
}






//------------ Проверка идентификации пользователя ----------
function checkUserIdent()
{
    global $USER;

    $user_id = $USER->GetID();
    $data    = false;

    if (!empty($user_id))
    {
        $arFilter           = array('ACTIVE' => 'Y', 'ID' => $user_id);
        $arParams['SELECT'] = array('*', 'UF_*');
        $data               = CUser::GetList(($by = 'ID'), ($order = 'ASC'), $arFilter, $arParams)->Fetch();
    }

    $userIdent = $data;

    return $userIdent;
}
//------------ /Проверка идентификации пользователя ----------






//------------ Проверка на наличие неоплаченных наложенных платежей ----------
function checkNalPay()
{
    global $USER;

    $data    = false;
    $user_id = $USER->getID();

    if ($user_id)
    {
        $arFilter           = array('ACTIVE' => 'Y', 'ID' => $user_id);
        $arParams['SELECT'] = array('*', 'UF_*');
        $arUser             = CUser::GetList(($by = 'ID'), ($order = 'ASC'), $arFilter, $arParams)->Fetch();

        $arFilter = Array("USER_ID" => $arUser['ID'], 'PAY_SYSTEM_ID' => 2, 'PAYED' => 'N');
        $db_sales = CSaleOrder::GetList(array("ID" => "DESC"), $arFilter);

        while ($ar_sales = $db_sales->Fetch())
        {
            if ($ar_sales['STATUS_ID'] !== 'F' && $ar_sales['STATUS_ID'] !== 'K')
            {
                $data[] = $ar_sales;
            }
        }
    }

    return $data;
}






//----------- Лимиты на способы оплаты ------------
function getOrderLimit()
{
    global $arOrderLims;

    $arOrderLims   = array();
    $arFilter      = array('IBLOCK_ID' => 20, 'ACTIVE' => 'Y', 'ID' => 92656);
    $arSelect      = array('ID', 'NAME', 'PROPERTY_MIN_LIMIT', 'PROPERTY_MID_LIMIT', 'PROPERTY_MAX_LIMIT');
    $arResult      = getElementList($arFilter, $arSelect)[0];

    if (!empty($arResult['PROPERTY_MIN_LIMIT_VALUE']))
    {
        $arOrderLims['MIN'] = (int) $arResult['PROPERTY_MIN_LIMIT_VALUE'];
    }
    if (!empty($arResult['PROPERTY_MID_LIMIT_VALUE']))
    {
        $arOrderLims['MID'] = (int) $arResult['PROPERTY_MID_LIMIT_VALUE'];
    }
    if (!empty($arResult['PROPERTY_MAX_LIMIT_VALUE']))
    {
        $arOrderLims['MAX'] = (int) $arResult['PROPERTY_MAX_LIMIT_VALUE'];
    }

    return $arOrderLims;
}
function getPersonLimit()
{
    global $arPersonLims;

    $arPersonLims = array();
    $arFilter     = array('IBLOCK_ID' => 20, 'ACTIVE' => 'Y', 'ID' => 92657);
    $arSelect     = array('ID', 'NAME', 'PROPERTY_MIN_LIMIT', 'PROPERTY_MID_LIMIT', 'PROPERTY_MAX_LIMIT');
    $arResult     = getElementList($arFilter, $arSelect)[0];

    if (!empty($arResult['PROPERTY_MIN_LIMIT_VALUE']))
    {
        $arPersonLims['MIN'] = (int) $arResult['PROPERTY_MIN_LIMIT_VALUE'];
    }
    if (!empty($arResult['PROPERTY_MID_LIMIT_VALUE']))
    {
        $arPersonLims['MID'] = (int) $arResult['PROPERTY_MID_LIMIT_VALUE'];
    }
    if (!empty($arResult['PROPERTY_MAX_LIMIT_VALUE']))
    {
        $arPersonLims['MAX'] = (int) $arResult['PROPERTY_MAX_LIMIT_VALUE'];
    }

    return $arPersonLims;
}
getPersonLimit();
getOrderLimit();
//----------- /Лимиты на способы оплаты ------------









//-------------- Получение свойства заказа -------------
/*
$props = getOrderProperties([
    'order' => 5
]);
*/
function getOrderProperties($params = [])
{
    if ($params && !empty($params['order'])) {

        $order = $params['order'];

        if (CModule::IncludeModule('sale')) {

            $props = Bitrix\Sale\Internals\OrderPropsValueTable::getList([
                'filter' => [
                    'ORDER_ID' => $order
                ]
            ])->fetchAll();

            if (!empty($props)) {

                return $props;
            }
        }
    }

    return false;
}
//-------------- /Получение свойства заказа -------------




//------------- Обновление свойства заказа -----------
function updateOrderProp($order_id, $prop_code, $prop_value)
{
    setlocale(LC_NUMERIC, 'C');

    $db_vals     = CSaleOrderPropsValue::GetList(array("ID" => "ASC"), array("ORDER_ID" => $order_id, "CODE" => $prop_code));
    $order_props = array();

    if ($arVals = $db_vals->Fetch())
    {
        $order_props = $arVals;
    }
    if ($order_props['ID'])
    {
        CSaleOrderPropsValue::Update($order_props['ID'], array('VALUE' => $prop_value));
    }
}
//------------- /Обновление свойства заказа -----------











// ----------- Проверка наличия пагинации ------------
function checkPagen()
{
    $pager = false;
    foreach ($_GET as $key => $arElem)
    {
        if (strpos($key, 'PAGEN_') !== false)
        {
            $pager = $_GET[$key];
            break;
        }
    }

    return $pager;
}





// -------- Скачивание картинок ---------
function downloadImg($img_name, $img_url)
{
    $ch    = curl_init($img_url);
    $f_img = fopen($_SERVER['DOCUMENT_ROOT'] . '/test/downloads/' . $img_name, 'wb');

    curl_setopt($ch, CURLOPT_FILE, $f_img);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    curl_close($ch);

    fclose($f_img);
}





// ------- Функция установки заглавной первой буквы --------
function firstLetUp($str, $code = null)
{

    if ($code == 'ORGANIZATSIYA')
        return $str;
  
    $first = mb_substr($str, 0, 1, 'UTF-8');
    $last  = mb_substr($str, 1);
    $first = mb_strtoupper($first);
    $last  = mb_strtolower($last);
    $str   = $first . $last;

    return $str;
}





// --------- Замена имени на новое ----------
function replaceItemName($old_name, $new_name)
{
    if (!empty($new_name) && !empty($old_name))
    {
        $count    = 0;
        $word_pos = 0;

        $arOldName = explode(' ', $old_name);
        $arNewName = explode(' ', $new_name);

        foreach ($arOldName as $key => $item) {
            foreach ($arNewName as $key2 => $subitem)
            {
                if (mb_strtolower($item) == mb_strtolower($subitem))
                {
                    $word_pos = $key;
                    $count++;

                    break;
                }
            }
        }

        if ($count > 0)
        {
            $arOldName[$word_pos] = $new_name;
        }
        else
        {
            $arOldName[0] = mb_strtolower($arOldName[0]);
            array_unshift($arOldName, $new_name);
        }

        return implode(' ', $arOldName);
    }
    else
    {
        return $old_name;
    }
}





// --------- Вывод числа в виде цены ---------
function setPrice($price)
{
    return number_format($price, 0, '', ' ');
}





// --------- Транслит слова в понятный ЧПУ ---------
function translit($s)
{
    $s = (string)$s;
    $s = strip_tags($s);
    $s = str_replace(array("\n", "\r"), " ", $s);
    $s = preg_replace("/\s+/", ' ', $s);
    $s = trim($s);
    $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s);
    $s = strtr($s, array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'ъ' => '', 'ь' => ''));
    $s = preg_replace("/[^0-9a-z-_ ]/i", "", $s);
    $s = str_replace(" ", "_", $s);

    return $s;
}




//-------- Получение полного пути до категории ----------
function makeFullPath($id)
{
    CModule::IncludeModule('iblock');
    $path = '/sale/';
    $rs = CIBlockSection::GetNavChain(IBLOCK_ID, $id, array('ID', 'CODE'));

    while ($ar = $rs->Fetch())
    {
        $path .= $ar['CODE'] . '/';
    }

    return $path;
}
// ============= /Дополнительные функции ===========








//----------- Список городов России ---------
global $cron;
if (!$cron && strpos($_SERVER['REQUEST_URI'], '/bitrix/') === false)
{
    global $locations;

    $obCache    = new CPHPCache;
    $CACHE_TIME = 60 * 60 * 60 * 24;
    $strCacheID = "locs";


    $sCustomCachePath = "/bitrix/cache";
    if ($obCache->StartDataCache($CACHE_TIME, $strCacheID, $sCustomCachePath))
    {
        Bitrix\Main\Loader::includeModule('sale');

        $locations = array();
        $db_vars = CSaleLocation::GetList(
            array(
                "SORT" => "ASC",
                "COUNTRY_NAME_LANG" => "ASC",
                "CITY_NAME_LANG" => "ASC"
            ),
            array("LID" => LANGUAGE_ID, '!=CITY_NAME' => false),
            false,
            false,
            array()
        );

        while ($vars = $db_vars->Fetch())
        {
            if (!empty($vars['CITY_NAME']))
            {
                $locations[] = htmlspecialchars($vars["CITY_NAME"]);
            }
        }

        $obCache->EndDataCache(array('locations' => $locations));

    }
    else
    {
        $locs      = $obCache->GetVars();
        $locations = $locs['locations'];
    }
}
//----------- /Список городов России ---------











// ----------- Обработчик регистрации и обновления данных пользователя ----------
AddEventHandler("main", "OnBeforeUserAdd", "OnBeforeUserRegisterHandler");
AddEventHandler("main", "OnBeforeUserUpdate", "OnBeforeUserRegisterHandler");
function OnBeforeUserRegisterHandler($args)
{
    $check = true;

    foreach ($args as $key => $value)
    {
        if ($key == 'NAME' || $key == 'LAST_NAME' || $key == 'SECOND_NAME')
        {
            if (preg_match("/[\d]{1,}/", $value) || preg_match("/[www.-\/]{1,}/", $value))
            {
                $GLOBALS['APPLICATION']->ThrowException('Поля "Имя", "Фамилия", "Отчество" могут содержать только буквы');
                return false;
            }
        }
    }


    return true;
}
// ----------- Обработчик регистрации и обновления данных пользователя ----------






//------------ Подсчет кол-ва товаров корзины -------------
function countBasketItems($number)
{
    // От 2 до 4
    if ((5 > $number % 10) and ($number % 10 > 1)) {
        $number = '<span data-total-count="'.$number.'">'. $number .'</span> товара';
    } else {
        // От 5 до *0
        if ((($number % 10 >= 5) and ($number % 10 <= 9)) or ($number % 10 == 0) or ($number % 100 == 11)) {
            $number = '<span data-total-count="'.$number.'">'. $number .'</span> товаров';
        } else {
            $number = '<span data-total-count="'.$number.'">'. $number .'</span> товар';
        }
    }

    return $number;
}
//------------ /Подсчет кол-ва товаров корзины -------------







//------------- Получение товара корзины -----------
function getBasketItemsByOrderID($order_id){
    $arValues = array();
    $arResult = CSaleBasket::GetList(array('ID' => 'ASC'), array('ORDER_ID' => $order_id), false, false, array());
    while ($arItem = $arResult->Fetch())
    {
        $arValues[] = getElementById($arItem['PRODUCT_ID']);
    }
    return $arValues;
}
//------------- /Получение товара корзины -----------







//------------- Получение списка товаров корзины ----------
function getBasketItems()
{
    $arBasketItems = array();
    $dbBasketItems = CSaleBasket::GetList(
            array(
                "NAME" => "ASC",
                "ID"   => "ASC"
            ),
            array(
                "FUSER_ID" => CSaleBasket::GetBasketUserID(),
                "LID"      => SITE_ID,
                "ORDER_ID" => "NULL"
            ),
            false,
            false,
            array("ID", "CALLBACK_FUNC", "MODULE", "PRODUCT_ID", "QUANTITY", "DELAY", "CAN_BUY", "PRICE", "WEIGHT")
        );
    while ($arItems = $dbBasketItems->Fetch())
    {
        if (strlen($arItems["CALLBACK_FUNC"]) > 0)
        {
            CSaleBasket::UpdatePrice($arItems["ID"],
                $arItems["CALLBACK_FUNC"],
                $arItems["MODULE"],
                $arItems["PRODUCT_ID"],
                $arItems["QUANTITY"]
            );
            $arItems = CSaleBasket::GetByID($arItems["ID"]);
        }
        $arBasketItems[] = $arItems;
    }

    return $arBasketItems;
}
//------------- /Получение списка товаров корзины ----------








//---------------- Привязка заказов к зарегестрированным пользователям ------------
function AgentSetOrderToNewUser()
{
    CModule::IncludeModule('main');
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');

    $arSelect     = array();
    $arFilter     = array('USER_ID' => 17);
    $arResult     = CSaleOrder::GetList(array('ID' => 'DESC'), $arFilter, false, false, $arSelect);


    while ($arOrder = $arResult->Fetch())
    {
        $user         = false;
        $arOrderProps = getOrderProperties(array('order' => $arOrder['ID']));
        $arUsrFilter  = array('EMAIL' => $arOrderProps[2]['VALUE'], 'LOGIN' => $arOrderProps[2]['VALUE']);
        $rsUser       = CUser::GetList(($by = 'ID'), ($order = 'DESC'), $arUsrFilter)->Fetch();

        if ($rsUser) {
            $user = true;
        } else {
            $arUsrFilter = array('PERSONAL_PHONE' => $arOrderProps[3]['VALUE']);
            $rsUser      = CUser::GetList(($by = 'ID'), ($order = 'DESC'), $arUsrFilter)->Fetch();

            if ($rsUser) {
                $user = true;
            }
        }

        if ($user) {
            CSaleOrder::Update($arOrder['ID'], array('USER_ID' => $rsUser['ID']));
        }
    }


    return 'AgentSetOrderToNewUser();';
}
//---------------- /Привязка заказов к зарегестрированным пользователям ------------





//----------------- АГЕНТ: ОБНОВЛЕНИЕ СВОЙСТВ ФИЛЬТРА ---------------
function AgentSetFilter1CValuse()
{
    \Bitrix\Main\Loader::IncludeModule('sale');
    \Bitrix\Main\Loader::IncludeModule('iblock');
    \Bitrix\Main\Loader::IncludeModule('catalog');


    $arProbs    = array('56', '72-96', '583м', '750м', '583');
    $arFilter   = array(
        'IBLOCK_ID' => 14,
        'ACTIVE'    => 'Y',
        //'=PROPERTY_CENA_ZA_GRAMM' => false,
        array(
        'LOGIC'     => 'OR',
            array('!=PROPERTY_PROBA'       => false),
            array('!=PROPERTY_CHISTYY_VES' => false),
        ),
    );
    $arSelect   = array('ID', 'NAME', 'PROPERTY_PROBA', 'PROPERTY_CHISTYY_VES');
    $arElements = CIBlockElement::GetList(array('ID' => 'ASC'), $arFilter, false, false, $arSelect);


    while ($arItem = $arElements->Fetch())
    {
        if (!empty($arItem['ID']))
        {
            //---------- Обновление ЦЕНЫ ЗА ГРАММ -------------
            if (!empty($arItem['PROPERTY_CHISTYY_VES_VALUE'])) {
                $PRICE = round((float) CPrice::GetBasePrice($arItem['ID'])['PRICE'] / (float)$arItem['PROPERTY_CHISTYY_VES_VALUE']);
                CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], array('CENA_ZA_GRAMM' => $PRICE));
            }
            //---------- /Обновление ЦЕНЫ ЗА ГРАММ -------------


            //---------- Обновление АНТИКВАРИАТА --------------
            in_array($arItem['PROPERTY_PROBA_VALUE'], $arProbs) ? $PROP_VALUE = 7 : $PROP_VALUE = 8;
            CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], array('ANTIKVARIAT' => $PROP_VALUE));
            //---------- /Обновление АНТИКВАРИАТА --------------
        }
    }


    Bitrix\Iblock\PropertyIndex\Manager::DeleteIndex(14);
    Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid(14);
}
//----------------- /АГЕНТ: ОБНОВЛЕНИЕ СВОЙСТВ ФИЛЬТРА ---------------






//--------------- АГЕНТ: ОТПРАВКА ПИСЬМА ПОКУПАТЕЛЮ ОБ ИЗМЕНЕНИИ СТАТУСА ТОВАРА --------------
function AgentCheckProductAvailable()
{
    CModule::IncludeModule('main');
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');


    $arFilter = array('IBLOCK_ID' => 24, '!=PROPERTY_CHECK_SEND_VALUE' => 'Y', 'ACTIVE' => 'Y');
    $arSelect = array('*', 'PROPERTY_FIO', 'PROPERTY_EMAIL', 'PROPERTY_ARTICLE');
    $arResult = getElementList($arFilter, $arResult);

    foreach ($arResult as $key => $arItem)
    {
        //--------- Формирование переменных -----------
        $name     = $arItem['PROPERTY_FIO_VALUE'];
        $email    = $arItem['PROPERTY_EMAIL_VALUE'];
        $article  = explode(': ', $arItem['PROPERTY_ARTICLE_VALUE'])[1];
        //--------- /Формирование переменных -----------





        //---------- Проверка статуса товара -----------
        $arSelect = array();
        $arFilter = array('IBLOCK_ID' => 14, 'PROPERTY_CML2_ARTICLE' => $article);
        $arElem   = CIBlockElement::GetList(array('ID' => 'ASC'), $arFilter, false, false, $arSelect)->GetNext();


        $obj      = (new FireStoreConnect(DEFAULT_PROJECT_NAME, '', '', DEFAULT_TOKEN))->GetByID('pledges/' . $arElem['XML_ID']);
        $prod_url = 'https://'. $_SERVER['HTTP_HOST'] .makeFullPath($arElem['~IBLOCK_SECTION_ID']).$arElem['CODE'].'/';

        $arr        = current($obj);
        $isSold     = current($arr['fields']['isSold1c']);
        $isReserved = current($arr['fields']['isReserved1c']);

        if (!current($arr['fields']['isReserved1c'])) {
            $isReserved = current($arr['fields']['isReserved']);
        }
        if (!current($arr['fields']['isSold1c'])) {
            $isSold = current($arr['fields']['isSold']);
        }
        if (strpos(mb_strtolower($arElem['NAME']), 'продан')) {
            $isSold = true;
        }
        //---------- /Проверка статуса товара -----------






        //----------- Формирование данных товара -------------
        $arElem['PRICE']                 = setPrice(CPrice::GetBasePrice($arElem['ID'])['PRICE']) .' ₽';
        $arElem['URL']                   = $prod_url;
        $arElem['PROPS']['ARTICLE']      = $article;
        $arElem['PROPS']['METALL']       = getElementPropertyByCode($arElem['IBLOCK_ID'], $arElem['ID'], 'METALL')[0];
        $arElem['PROPS']['PROBA']        = getElementPropertyByCode($arElem['IBLOCK_ID'], $arElem['ID'], 'PROBA')[0];
        $arElem['PROPS']['OBSHCHIY_VES'] = getElementPropertyByCode($arElem['IBLOCK_ID'], $arElem['ID'], 'OBSHCHIY_VES')[0];


        $prop_text = '';
        $count     = 0;
        foreach ($arElem['PROPS'] as $key => $arProp) {
            if ($key == 'ARTICLE')
                continue;

            if ($count > 0 && !empty($arProp['VALUE'])) {
                $prop_text .= ', ';
            }
            $prop_text .= $arElem['NAME'] .': '. firstLetUp($arProp['VALUE']);
            $count++;
        }


        $img_id        = $arElem['DETAIL_PICTURE'];
        $img           = CFile::ResizeImageGet($img_id, array("width"=>350,"height"=>350),BX_RESIZE_IMAGE_PROPORTIONAL,true,false,false,80);
        $hash          = base64_encode(urlencode($email.'#'.$arElem['NAME'].': '.$article));
        $arElem['IMG'] = $img;
        //----------- /Формирование данных товара -------------







        //---------- Отправка письма --------------
        /*
        setlocale(LC_ALL, 'ru_RU.UTF-8');
        $html = '<p>Уважаемы (ая) '. $name .'</p>';
        $date = strftime('%e %B', $arItem['DATE_CREATE_UNIX']).' '.date('Y года');
        */



        if ($isSold) {
            $price    = CPrice::GetBasePrice($arElem['ID'])['PRICE'];
            $arProdRs = array();
            $arFilter = array(
                'LOGIC' => 'AND',
                array(
                    'IBLOCK_ID'         => $arElem['IBLOCK_ID'],
                    'SECTION_ID'        => $arElem['~IBLOCK_SECTION_ID'],
                    '<=CATALOG_PRICE_1' => $price + ($price * 40 / 100),
                    '!=ID'              => $arElem['ID']
                ),
                array(
                    'ACTIVE'             => 'Y',
                    '=PROPERTY_RESERVED' => false,
                    '>=CATALOG_PRICE_1'  => $price,
                )
            );

            $arResult = CIBlockElement::GetList(array('RAND' => 'DESC'), $arFilter, false, array('nPageSize' => 2), array());
            while ($arProd = $arResult->Fetch())
            {
                //----------- Формирование данных товара -------------
                $arProd['PRICE']                 = setPrice(CPrice::GetBasePrice($arProd['ID'])['PRICE']) .' ₽';
                $arProd['URL']                   = 'https://'.$_SERVER['SERVER_NAME'].makeFullPath($arProd['IBLOCK_SECTION_ID']).$arProd['CODE'].'/';
                $arProd['PROPS']['ARTICLE']      = getElementPropertyByCode($arProd['IBLOCK_ID'], $arProd['ID'], 'CML2_ARTICLE')[0];
                $arProd['PROPS']['METALL']       = getElementPropertyByCode($arProd['IBLOCK_ID'], $arProd['ID'], 'METALL')[0];
                $arProd['PROPS']['PROBA']        = getElementPropertyByCode($arProd['IBLOCK_ID'], $arProd['ID'], 'PROBA')[0];
                $arProd['PROPS']['OBSHCHIY_VES'] = getElementPropertyByCode($arProd['IBLOCK_ID'], $arProd['ID'], 'OBSHCHIY_VES')[0];


                $prop_text = '';
                $count     = 0;
                foreach ($arProd['PROPS'] as $key => $arProp) {
                    if ($key == 'ARTICLE')
                        continue;

                    if ($count > 0 && !empty($arProp['VALUE'])) {
                        $prop_text .= ', ';
                    }
                    $prop_text .= $arProp['NAME'] .': '. firstLetUp($arProp['VALUE']);
                    $count++;
                }
                $arProd['PROP_TEXT'] = $prop_text;


                $img_id = $arProd['DETAIL_PICTURE'];
                $img    = CFile::ResizeImageGet($img_id, array("width"=>350,"height"=>350),BX_RESIZE_IMAGE_PROPORTIONAL,true,false,false,80);
                $arProd['IMG'] = $img;
                //----------- /Формирование данных товара -------------

                $arProdRs[] = $arProd;
            }

            include($_SERVER['DOCUMENT_ROOT'] . '/local/include_area/mails/product_is_sold.php');
            $MAIL_ID = 133;
        } elseif (!$isReserved) {
            include($_SERVER['DOCUMENT_ROOT'] . '/local/include_area/mails/product_can_buy.php');
            $MAIL_ID = 132;
        }


        if ($isSold || !$isReserved) {
            //-------- ПОЧТА: Отправка письма менеджеру --------
            /*
            $arEventFields = array(
                "TEXT"     => $html,
                "EMAIL_TO" => 'admin@lombardunion.com'
            );
            CEvent::SendImmediate("FEEDBACK_FORM", "s1", $arEventFields, 'N', $MAIL_ID);
            */
            //-------- /ПОЧТА: Отправка письма менеджеру --------



            //-------- ПОЧТА: Отправка письма клиенту ------------
            $arEventFields = array(
                "TEXT"     => $html2,
                "EMAIL_TO" => $email
            );
            CEvent::SendImmediate("FEEDBACK_FORM", "s1", $arEventFields, 'N', $MAIL_ID);
            //-------- /ПОЧТА: Отправка письма клиенту ------------


            CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], array('CHECK_SEND' => 6));
        }
        //---------- /Отправка письма --------------
    }

    return 'AgentCheckProductAvailable();';
}
//--------------- /АГЕНТ: ОТПРАВКА ПИСЬМА ПОКУПАТЕЛЮ ОБ ИЗМЕНЕНИИ СТАТУСА ТОВАРА --------------






//--------------- АГЕНТ: ОБНОВЛЕНИЕ СТАТУСА ТОВАРА ИЗ ФБ --------------
function AgentSetCatalogElementAvailable()
{

    $debugStart = '/-----START ' . date('d.m.y H:i:s') . '-----/';
    $debugPath = $_SERVER['DOCUMENT_ROOT'] . '/debug_setCatalogAvailable.txt';

    file_put_contents($debugPath, $debugStart . PHP_EOL, FILE_APPEND);

    require_once("/home/bitrix/www/local/php_interface/include/FireStoreConnect.php");
    require_once("/home/bitrix/www/local/php_interface/include/FireStoreConnect_dept.php");
    require_once("/home/bitrix/www/local/php_interface/include/FireStoreTicket.php");
    require_once("/home/bitrix/www/local/php_interface/include/FireStoreCart.php");

    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');

    //-------------- Получение токена -----------
    $curl   = curl_init();
    $arPost =
    [
        "email"             => 'fbbitrix@consultcentr.ru',
        "password"          => '!Iy89UFr*t6{tyuI-8;TYG',
        "returnSecureToken" => true
    ];
    $postBody = json_encode($arPost);
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => "https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPassword?key=AIzaSyDmWKcoMRgK6t8eC_fpppkKr0MmWS7HtN0",
        CURLOPT_HTTPHEADER    => array('Content-Type: application/json', 'Content-Length: ' . strlen($postBody)),
        CURLOPT_USERAGENT     => 'cURL',
        CURLOPT_POST          => true,
        CURLOPT_POSTFIELDS    => $postBody
    ));
    $response     = curl_exec($curl);
    curl_close($curl);
    $response     = json_decode($response, true);
    $query_token  = $response['idToken'];
    //-------------- /Получение токена -----------





    //------------- Формирование данных запроса --------------
    $query_url    = 'https://firestore.googleapis.com/v1/projects/node-1c-01/databases/(default)/documents:batchGet';
    $query_method = 'documents:batchGet';
    $query_params = array();
    $query_prod   = 'projects/node-1c-01/databases/(default)/documents/pledges/';

    $arFilter     = array('IBLOCK_ID' => 14, 'ACTIVE' => 'Y');
    $arSelect     = array("ID","IBLOCK_ID","XML_ID");
    $arResult     = getElementList($arFilter, $arSelect);
    //------------- /Формирование данных запроса --------------





    //----------- Получение ответа из сервиса ----------
    $count = 0;
    foreach ($arResult as $el_key => $arElem) {
        $curl       = curl_init();
        $query_elem = json_encode(['documents' => array($query_prod . $arElem['XML_ID'])]);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $query_url,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($query_elem),
                'Authorization: Bearer ' . $query_token
            ),
            CURLOPT_USERAGENT      => 'cURL',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $query_elem
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        foreach (json_decode($response, true) as $document) {
            $result[] = $document['found'];
        }

        $arr        = current($result);
        $available  = true;
        foreach ($arr['fields'] as $key => $item) {
            if (strpos($key, 'isSold') !== false || strpos($key, 'isReserved') !== false) {
                if ($item['booleanValue']) {
                    $available = false;
                    break;
                }
            }
        }

        /*
        $isSold     = $arr['fields']['isSold1c']['booleanValue'];
        $isReserved = $arr['fields']['isReserved1c']['booleanValue'];
        unset($result);

        if (empty($arr['fields']['isReserved1c']['booleanValue'])) {
            $isReserved = $arr['fields']['isReserved']['booleanValue'];
        }
        if (empty($arr['fields']['isSold1c']['booleanValue'])) {
            $isSold = $arr['fields']['isSold']['booleanValue'];
        }
        $available = (!$isSold && !$isReserved);
        */
        //----------- /Получение ответа из сервиса ----------






        //----------- Установка статуса товара в BITRIX ------------
        if (!$available) {
            $arFields = array('RESERVED' => 9, 'USER_SORT' => 500);
        } else {
            $arFields = array('RESERVED' => '', 'USER_SORT' => 100);
        }
        CIBlockElement::SetPropertyValuesEx($arElem['ID'], $arElem['IBLOCK_ID'], $arFields);
        //----------- /Установка статуса товара в BITRIX ------------




        //------------- Проверка на старые бронирования ------------
        if ($arr['fields']['isReserved']['booleanValue'] && !$arr['fields']['isSold']['booleanValue']
            &&
            !$arr['fields']['isSold1c']['booleanValue'] && !$arr['fields']['isReserved1c']['booleanValue']
        ) {
            $arBasketItem = CSaleOrder::GetList(array('DATE_INSERT' => 'DESC'), array('BASKET_PRODUCT_ID' => $arElem['ID']))->Fetch();
            $reserve_end  = strtotime('+ 5 days', strtotime($arBasketItem['DATE_INSERT']));
            $today        = strtotime(date('d.m.Y'));

            if ($reserve_end < $today || empty($arBasketItem['ID'])) {
                if (!empty($arElem['XML_ID'])) {
                    FireStoreCart::CancelReserve($arElem['XML_ID']);
                    CIBlockElement::SetPropertyValuesEx($arElem['ID'], $arElem['IBLOCK_ID'], array('RESERVED' => '', 'USER_SORT' => 100));
                }
            }
        }
        //------------- /Проверка на старые бронирования ------------



        //file_put_contents('/home/bitrix/www/response.txt', 'ID__'.$arElem['ID'].', ', FILE_APPEND);
        file_put_contents($debugPath, 'item ' . $count . "\t" . date('H:i:s') . PHP_EOL, FILE_APPEND);
        $count++;
    }

    //file_put_contents('/home/bitrix/www/response.txt', PHP_EOL.'=== End__'.date('d-m-Y H:i').': '.$count." ===".PHP_EOL, FILE_APPEND);
    $debugEnd = '/-----END ' . date('d.m.y H:i:s') . '-----/';
    file_put_contents($debugPath, $debugEnd . PHP_EOL, FILE_APPEND);
    return 'AgentSetCatalogElementAvailable();';
}

function AgentSetCatalogElementAvailableCustom(){
    require_once("/home/bitrix/www/local/php_interface/include/FireStoreConnect.php");
    require_once("/home/bitrix/www/local/php_interface/include/FireStoreConnect_dept.php");
    require_once("/home/bitrix/www/local/php_interface/include/FireStoreTicket.php");
    require_once("/home/bitrix/www/local/php_interface/include/FireStoreCart.php");

    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');

    //-------------- Получение токена -----------
    $curl   = curl_init();
    $arPost =
    [
        "email"             => 'fbbitrix@consultcentr.ru',
        "password"          => '!Iy89UFr*t6{tyuI-8;TYG',
        "returnSecureToken" => true
    ];
    $postBody = json_encode($arPost);
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => "https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPassword?key=AIzaSyDmWKcoMRgK6t8eC_fpppkKr0MmWS7HtN0",
        CURLOPT_HTTPHEADER    => array('Content-Type: application/json', 'Content-Length: ' . strlen($postBody)),
        CURLOPT_USERAGENT     => 'cURL',
        CURLOPT_POST          => true,
        CURLOPT_POSTFIELDS    => $postBody
    ));
    $response     = curl_exec($curl);
    curl_close($curl);
    $response     = json_decode($response, true);
    $query_token  = $response['idToken'];
    //-------------- /Получение токена -----------





    //------------- Формирование данных запроса --------------
    $query_url    = 'https://firestore.googleapis.com/v1/projects/node-1c-01/databases/(default)/documents:batchGet';
    $query_method = 'documents:batchGet';
    $query_params = array();
    $query_prod   = 'projects/node-1c-01/databases/(default)/documents/pledges/';

    $arFilter     = array('IBLOCK_ID' => 14, 'ACTIVE' => 'Y', 'PROPERTY_RESERVED' => array(9));
    $arSelect     = array();
    $arResult     = getElementList($arFilter, $arSelect);
    //------------- /Формирование данных запроса --------------



    //----------- Получение ответа из сервиса ----------
    $count = 0;
    foreach ($arResult as $el_key => $arElem) {
        $curl       = curl_init();
        $query_elem = json_encode(['documents' => array($query_prod . $arElem['XML_ID'])]);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $query_url,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($query_elem),
                'Authorization: Bearer ' . $query_token
            ),
            CURLOPT_USERAGENT      => 'cURL',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $query_elem
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        foreach (json_decode($response, true) as $document) {
            $result[] = $document['found'];
        }

        $arr        = current($result);
        $available  = true;
        foreach ($arr['fields'] as $key => $item) {
            if (strpos($key, 'isSold') !== false || strpos($key, 'isReserved') !== false) {
                if ($item['booleanValue']) {
                    $available = false;
                    break;
                }
            }
        }


        /*
        $isSold     = $arr['fields']['isSold1c']['booleanValue'];
        $isReserved = $arr['fields']['isReserved1c']['booleanValue'];
        unset($result);

        if (empty($arr['fields']['isReserved1c']['booleanValue'])) {
            $isReserved = $arr['fields']['isReserved']['booleanValue'];
        }
        if (empty($arr['fields']['isSold1c']['booleanValue'])) {
            $isSold = $arr['fields']['isSold']['booleanValue'];
        }
        $available = (!$isSold && !$isReserved);
        */
        //----------- /Получение ответа из сервиса ----------






        //----------- Установка статуса товара в BITRIX ------------
        if (!$available) {
            $arFields = array('RESERVED' => 9, 'USER_SORT' => 500);
        } else {
            $arFields = array('RESERVED' => '', 'USER_SORT' => 100);
        }
        CIBlockElement::SetPropertyValuesEx($arElem['ID'], $arElem['IBLOCK_ID'], $arFields);
        //----------- /Установка статуса товара в BITRIX ------------




        //------------- Проверка на старые бронирования ------------
        if ($arr['fields']['isReserved']['booleanValue'] && !$arr['fields']['isSold']['booleanValue']
            &&
            !$arr['fields']['isSold1c']['booleanValue'] && !$arr['fields']['isReserved1c']['booleanValue']
        ) {
            $arBasketItem = CSaleOrder::GetList(array('DATE_INSERT' => 'DESC'), array('BASKET_PRODUCT_ID' => $arElem['ID']))->Fetch();
            $reserve_end  = strtotime('+ 5 days', strtotime($arBasketItem['DATE_INSERT']));
            $today        = strtotime(date('d.m.Y'));

            if ($reserve_end < $today || empty($arBasketItem['ID'])) {
                if (!empty($arElem['XML_ID'])) {
                    FireStoreCart::CancelReserve($arElem['XML_ID']);
                    CIBlockElement::SetPropertyValuesEx($arElem['ID'], $arElem['IBLOCK_ID'], array('RESERVED' => '', 'USER_SORT' => 100));
                }
            }
        }
        //------------- /Проверка на старые бронирования ------------



        //file_put_contents('/home/bitrix/www/response.txt', 'ID__'.$arElem['ID'].', ', FILE_APPEND);
        $count++;
    }

    //file_put_contents('/home/bitrix/www/response.txt', PHP_EOL.'=== End__'.date('d-m-Y H:i').': '.$count." ===".PHP_EOL, FILE_APPEND);


    return 'AgentSetCatalogElementAvailableCustom();';
}
//--------------- /АГЕНТ: ОБНОВЛЕНИЕ СТАТУСА ТОВАРА ИЗ ФБ --------------






//----------- АГЕНТ: Заолнение свойства Цена у товаров ------------
function AgentSetCatalogElementPrices()
{
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');

    $arFilter = array('IBLOCK_ID' => 14, 'ACTIVE' => 'Y');
    $arSelect = array();
    $arResult = CIBlockElement::GetList(array('ID' => 'ASC'), $arFilter, false, false, $arSelect);

    while ($arItem = $arResult->Fetch()) {
        if (!empty($arItem['ID']))
        {
            $elem_price = (float) CPrice::GetBasePrice($arItem['ID'])['PRICE'];

            if ($elem_price > 0) {
                CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], array('PRICE' => (int) $elem_price));
            }
        }
    }


    Bitrix\Iblock\PropertyIndex\Manager::DeleteIndex(14);
    Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid(14);

    return 'AgentSetCatalogElementPrices();';
}
//----------- /АГЕНТ: Заолнение свойства Цена у товаров ------------







//---------- АГЕНТ: Добавление новым товарам тега "Новинка" -------------
function AgentSetNewTagToElement()
{
    CModule::IncludeModule('main');
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');


    //-------- Удаление у старых товаров тега "Новинка" ---------
    $arFilter = array('IBLOCK_ID' => 14, 'PROPERTY_TAGS' => array(10));
    $arResult = getElementList($arFilter, array());

    foreach ($arResult as $key => $arElem) {
        $arProps  = false;
        $arPropRs = CIBlockElement::GetPropertyValues($arElem['IBLOCK_ID'], array('ID' => $arElem['ID']), true, array('ID' => array(463)));

        while ($arValue = $arPropRs->Fetch()) {
            foreach ($arValue[463] as $val_key => $val) {
                if ((int) $val !== 10) {
                    $arProps[] = $val;
                }
            }
        }

        CIBlockElement::SetPropertyValuesEx($arElem['ID'], $arElem['IBLOCK_ID'], array('TAGS' => $arProps));
    }
    //-------- /Удаление у старых товаров тега "Новинка" ---------



    //-------- Добавление новым товарам тега "Новинка" ------------
    $arFilter = array('IBLOCK_ID' => 14, 'ACTIVE' => 'Y');
    $arSelect = array();
    $arResult = getSectionList($arFilter, $arSelect);

    foreach ($arResult as $sect_key => $arSection)
    {
        $arFilter = array('IBLOCK_ID' => 14, 'SECTION_ID' => $arSection['ID'], 'ACTIVE' => 'Y', 'INCLUDE_SUBSECTIONS' => 'Y');
        $arElemRs = CIBlockElement::GetList(array('ID' => 'DESC'), $arFilter, false, array('nPageSize' => 20), array());

        while ($arElem = $arElemRs->Fetch())
        {
            $arProps  = array(10);
            $arPropRs = CIBlockElement::GetPropertyValues($arElem['IBLOCK_ID'], array('ID' => $arElem['ID']), true, array('ID' => array(463)));
            while ($arValue = $arPropRs->Fetch()) {
                foreach ($arValue[463] as $val_key => $val) {
                    if (!in_array($val, $arProps)) {
                        array_push($arProps, $val);
                    }
                }
            }

            CIBlockElement::SetPropertyValuesEx($arElem['ID'], $arElem['IBLOCK_ID'], array('TAGS' => $arProps));
        }
    }
    //-------- /Добавление новым товарам тега "Новинка" ------------


    return 'AgentSetNewTagToElement();';
}
//---------- /АГЕНТ: Добавление новым товарам тега "Новинка" -------------






//----------- АГЕНТ: Обновление резервирования товаров -------------
function AgentCheckCatalogElementReserve()
{
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');

    $today    = strtotime(date('d.m.Y'));
    $arFilter = array('IBLOCK_ID' => 14, 'PROPERTY_RESERVED' => 9);
    $arResult = CIBlockElement::GetList(false, $arFilter);

    while ($arItem = $arResult->Fetch()) {
        $check = getFireBaseElementAvailableArray(array('ID' => $arItem['ID']));

        if (current($check['fields']['isReserved']) && !current($check['fields']['isSold'])
            &&
            !current($check['fields']['isSold1c']) && !current($check['fields']['isReserved1c'])
        ) {
            $arBasketItem = CSaleOrder::GetList(array('DATE_INSERT' => 'DESC'), array('BASKET_PRODUCT_ID' => $arItem['ID']))->Fetch();
            $reserve_end  = strtotime('+ 5 days', strtotime($arBasketItem['DATE_INSERT']));

            if ($reserve_end < $today || empty($arBasketItem['ID'])) {
                if (!empty($arItem['XML_ID'])) {
                    FireStoreCart::CancelReserve($arItem['XML_ID']);
                    CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], array('RESERVED' => ''));
                }
            }
        }
    }
}
//----------- /АГЕНТ: Обновление резервирования товаров -------------




//--------------- АГЕНТ: Адаление старых корзин ---------------
function AgentDeleteOldBaskets(){
    if (CModule::IncludeModule("sale") && CModule::IncludeModule("catalog") ){
        global $DB;

        // сроком старше одного дня
        $nDays  = 1;
        $nDays  = IntVal($nDays);
        $strSql =
            "SELECT f.ID ".
            "FROM b_sale_fuser f ".
            "LEFT JOIN b_sale_order o ON (o.USER_ID = f.USER_ID) ".
            "WHERE ".
            "   TO_DAYS(f.DATE_UPDATE)<(TO_DAYS(NOW())-".$nDays.") ".
            "   AND o.ID is null ".
            "   AND f.USER_ID is null ".
            "LIMIT 3000";
        $db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
        while ($ar_res = $db_res->Fetch()){
            CSaleBasket::DeleteAll($ar_res["ID"], false);
            CSaleUser::Delete($ar_res["ID"]);
        }
    }

    return "AgentDeleteOldBaskets();";
}
//--------------- /АГЕНТ: Адаление старых корзин ---------------







//-------------- Проверка статуса товара по фильтру FireBase -------------
function getFireBaseElementAvailable($filter)
{
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');

    return true;

    //-------------- Получение токена -----------
    $curl   = curl_init();
    $arPost =
    [
        "email"             => 'fbbitrix@consultcentr.ru',
        "password"          => '!Iy89UFr*t6{tyuI-8;TYG',
        "returnSecureToken" => true
    ];
    $postBody = json_encode($arPost);
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => "https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPassword?key=AIzaSyDmWKcoMRgK6t8eC_fpppkKr0MmWS7HtN0",
        CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Content-Length: ' . strlen($postBody)),
        CURLOPT_USERAGENT => 'cURL',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postBody
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $response     = json_decode($response, true);
    $query_token  = $response['idToken'];
    //-------------- /Получение токена -----------



    //------------- Формирование данных запроса --------------
    $query_url    = 'https://firestore.googleapis.com/v1/projects/node-1c-01/databases/(default)/documents:batchGet';
    $query_params = array();
    $query_prod   = 'projects/node-1c-01/databases/(default)/documents/pledges/';

    $arSelect     = array();
    $arResult     = CIBlockElement::GetList(array('ID' => 'DESC'), $filter, false, false, $arSelect);
    //------------- /Формирование данных запроса --------------






    //----------- Получение ответа из сервиса ----------
    while ($arElem = $arResult->Fetch())
    {
        $curl       = curl_init();
        $query_elem = json_encode(['documents' => array($query_prod . $arElem['XML_ID'])]);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $query_url,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($query_elem),
                'Authorization: Bearer ' . $query_token
            ),
            CURLOPT_USERAGENT  => 'cURL',
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $query_elem
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        foreach (json_decode($response, true) as $document)
        {
            $result[] = $document['found'];
        }

        $arr        = current($result);

        $available  = true;
        foreach ($arr['fields'] as $key => $item) {
            if (strpos($key, 'isSold') !== false || strpos($key, 'isReserved') !== false) {
                if ($item['booleanValue']) {
                    $available = false;
                    break;
                }
            }
        }


        /*
        $isSold     = current($arr['fields']['isSold1c']);
        $isReserved = current($arr['fields']['isReserved1c']);
        $check      = isset($arr['fields']['isReserved1c']);

        if (!current($arr['fields']['isReserved1c'])) {
            $isReserved = current($arr['fields']['isReserved']);
            $check      = isset($arr['fields']['isReserved']);
        }
        if (!current($arr['fields']['isSold1c'])) {
            $isSold = current($arr['fields']['isSold']);
        }


        $available = (!$isSold && !$isReserved);
        */
        //----------- /Получение ответа из сервиса ----------
    }

    return $available;
}
function getFireBaseElementAvailableArray($filter)
{
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');



    //-------------- Получение токена -----------
    $curl   = curl_init();
    $arPost =
    [
        "email"             => 'fbbitrix@consultcentr.ru',
        "password"          => '!Iy89UFr*t6{tyuI-8;TYG',
        "returnSecureToken" => true
    ];
    $postBody = json_encode($arPost);
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => "https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPassword?key=AIzaSyDmWKcoMRgK6t8eC_fpppkKr0MmWS7HtN0",
        CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Content-Length: ' . strlen($postBody)),
        CURLOPT_USERAGENT => 'cURL',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postBody
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $response     = json_decode($response, true);
    $query_token  = $response['idToken'];
    //-------------- /Получение токена -----------



    //------------- Формирование данных запроса --------------
    $query_url    = 'https://firestore.googleapis.com/v1/projects/node-1c-01/databases/(default)/documents:batchGet';
    $query_params = array();
    $query_prod   = 'projects/node-1c-01/databases/(default)/documents/pledges/';

    $arSelect     = array();
    $arResult     = CIBlockElement::GetList(array('ID' => 'DESC'), $filter, false, false, $arSelect);
    //------------- /Формирование данных запроса --------------






    //----------- Получение ответа из сервиса ----------
    while ($arElem = $arResult->Fetch())
    {
        $curl       = curl_init();
        $query_elem = json_encode(['documents' => array($query_prod . $arElem['XML_ID'])]);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $query_url,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($query_elem),
                'Authorization: Bearer ' . $query_token
            ),
            CURLOPT_USERAGENT  => 'cURL',
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $query_elem
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        foreach (json_decode($response, true) as $document)
        {
            $result[] = $document['found'];
        }


        return current($result);
        //----------- /Получение ответа из сервиса ----------
    }
}
//-------------- /Проверка статуса товара по фильтру FireBase -------------


// ------------ Добавление скидок в массив тегов ------------
function setSalesToTags($saleIds, $arTags)
{
    if (!empty($saleIds))
    {
        $arSales  = getElementList(array('IBLOCK_ID' => 25, 'ACTIVE' => 'Y', 'ID' => $saleIds), array('PROPERTY_SALE', 'PROPERTY_TEXT'));
        foreach ($arSales as $sale_key => $sale)
        {
            if (!empty($sale['PROPERTY_TEXT_VALUE'])) {
                $arTags[] = array('VALUE' => $sale['PROPERTY_TEXT_VALUE'], 'XML_ID' => 'new');
            }
        }
    }

    return $arTags;
}
// ------------ /Добавление скидок в массив тегов ------------



//-------------- Получение статуса товара по фильтру BITRIX ----------
function getCatalogElementAvailable($filter)
{
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('catalog');
    CModule::IncludeModule('sale');

    $arSelect = array('PROPERTY_RESERVED');
    $arResult = CIBlockElement::GetList(array('ID' => 'ASC'), $filter, false, false, $arSelect)->Fetch();
    $response = true;

    if ($arResult['PROPERTY_RESERVED_VALUE'] == 'Y')
    {
        $response = false;
    }

    return $response;
}
//-------------- /Получение статуса товара по фильтру BITRIX ----------

//-------------- Getting parrent section of element --------------//
function getParrentSection($elementId, $depth = 1)
{
    CModule::IncludeModule('iblock');
    $id = CIBlockElement::GetById($elementId)->Fetch()["IBLOCK_SECTION_ID"];
    $sec = NULL;
    do {
        $parrentSection = CIBlockSection::GetList(
            array(),
            array(
                "ID" => $id,
            ),
            false,
            false,
            array("ID", "DEPTH_LEVEL", "IBLOCK_SECTION_ID")
        );
        $sec = $parrentSection->Fetch();
        $id = $sec["IBLOCK_SECTION_ID"];
    } while ($sec["DEPTH_LEVEL"] > $depth);

    return $sec["ID"];
}
//-------------- /Getting parrent section of element --------------//

//include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php");

function getMetalCurrency() {
    require($_SERVER["DOCUMENT_ROOT"]."/local/cron/loadMetalCurrency.php");
    $currency = getMetalPrices();

    COption::SetOptionString('grain.customsettings', 'GOLD_CBR', $currency['gold']);
    COption::SetOptionString('grain.customsettings', 'SILVER_CBR', $currency['silver']);

    return 'getMetalCurrency();';
}

AddEventHandler("iblock", "OnBeforeIBlockSectionAdd", "ModifySectionCode");
AddEventHandler("iblock", "OnBeforeIBlockSectionUpdate", "ModifySectionCode");

function ModifySectionCode(&$arFields) {
    if ( $arFields['IBLOCK_ID'] === '14' ) {
        $code = $arFields['CODE'];
        $pos = strpos( $code, '_cat' );
        $code = substr( $code, 0, $pos === false ? strlen( $code ) : $pos );

        $arFields['CODE'] = $code . "_cat";
    }
}

//
$getMinimumSumForOrderFromProperty = CIBlockElement::GetProperty("33", "126697", array(), Array("CODE" => "MINIMUM_SUM_FOR_ORDER"));
while ($arProp = $getMinimumSumForOrderFromProperty->Fetch())
{
    $arrMinimumSumForOrderFromProperty = $arProp;
}
unset($getMinimumSumForOrderFromProperty);
$GLOBALS['MINIMUM_SUM_FOR_ORDER'] = $arrMinimumSumForOrderFromProperty['VALUE'];

function getParentSections($section_id){

    $result = array();

    $nav = CIBlockSection::GetNavChain(false, $section_id);
   
    while($v = $nav->GetNext()) {
        if($v['ID']) $result[] = $v['ID'];
    }

   return $result;
}

function sectionsHasButton($iblock_id = null, $section_ids = []){
    if($iblock_id == null || empty($section_ids)) return false;

    $db_list = CIBlockSection::GetList(
        [$by=>$order], 
        ['IBLOCK_ID' => $iblock_id, 'ID'=> $section_ids ], 
        true,
        ['ID','UF_ACTIVE_BTN']
    );

    while($section = $db_list->GetNext()){
        if($section['UF_ACTIVE_BTN'] !== null) return true;
    }

    return false;
}

function hasParentWithButton($iblock_id, $section_ids){
    return sectionsHasButton(
        $iblock_id,
        getParentSections($section_ids)
    );
}
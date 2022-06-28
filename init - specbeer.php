<?php

AddEventHandler("main", "OnBeforeEventAdd", "OnBeforeEventAddHandlerOrder");

function OnBeforeEventAddHandlerOrder(&$event, &$lid, &$arFields)
{
    if ($event == "SALE_NEW_ORDER") {
        $order_id = $arFields["ORDER_REAL_ID"];

        if ($order_id > 0) {
            CModule::IncludeModule("sale");

            $rsOrderProps = CSaleOrderPropsValue::GetOrderProps($order_id);
            $prop_info = "Информация о заказе:<br>";

            while ($ar = $rsOrderProps->GetNext()) {
                switch ($ar["CODE"]) {
                    case 'FIO';
                    case 'EMAIL';
                    case 'PHONE';
                    case 'DATE';
                    case 'ADDRESS';
                        $prop_info .= $ar["NAME"] . " : " . $ar["VALUE"] . "<br>";

                        break;
                }
            }

            $arFields["SHOW_ALL_PROPS"] = $prop_info;
        }
    }
}
<?
/*
echo "some text";
exit();
*/
// Получить цену товаров при создании корзины
use Bitrix\Main;
use Bitrix\Main\Entity;

$eventManager = Main\EventManager::getInstance();
$eventManager->addEventHandler("sale", "OnSaleComponentOrderCreated", "xakpl_OnSaleComponentOrderCreated");
//AddEventHandler("sale", "OnSaleComponentOrderCreated", "xakpl_OnSaleComponentOrderCreated");
function xakpl_OnSaleComponentOrderCreated($order, &$arUserResult, $request, &$arParams, &$arResult, &$arDeliveryServiceAll, &$arPaySystemServiceAll){
    $_SESSION['XAKPL_ORDER_BASKET_PRICE'] = $order->getBasket()->getPrice();
}
//OnSaleComponentOrderOneStepComplete
// Изменить цену доставки после получения расчёта стоимости
$eventManager->addEventHandler("sale", "onSaleDeliveryServiceCalculate", "xakpl_onSaleDeliveryServiceCalculate");
//AddEventHandler("sale", "onSaleDeliveryServiceCalculate", "xakpl_onSaleDeliveryServiceCalculate");
function xakpl_onSaleDeliveryServiceCalculate($result, $shipment, $deliveryID){
  // Проверка id службы доставки
  if( $deliveryID == 16 ){
        if(isset($_SESSION['XAKPL_ORDER_BASKET_PRICE']) )
        {
            $basketPrice = $_SESSION['XAKPL_ORDER_BASKET_PRICE'];
			// Получаем регион доставки
			//if (region == 216) {}
			// Получаем цену доставки
            $deliveryPrice = $result->getDeliveryPrice();

            $newValue = 350;
            if($basketPrice > 5000){
				// Считаем цену на доставку со скидкой 100%
                $newValue = 0;
            }
			// Записываем новое значение цены на доставку
			$shipment->setBasePriceDelivery($newValue, true);

        }
  }
}
\Bitrix\Main\EventManager::getInstance()->addEventHandler('sale', 'onSaleDeliveryServiceCalculate', 'yourHandler');

function yourHandler(\Bitrix\Main\Event $event)
{
    $calcResult = $event->getParameter('RESULT');
    $shipment = $event->getParameter('SHIPMENT');

    // например, прибавим 200 рублей к стоимости доставки
    $newPrice = $calcResult->getDeliveryPrice() + 200;
    $calcResult->setDeliveryPrice($newPrice);

    return new \Bitrix\Main\EventResult(
        \Bitrix\Main\EventResult::SUCCESS,
        array(
            "RESULT" => $calcResult,
        )
    );
}

?>
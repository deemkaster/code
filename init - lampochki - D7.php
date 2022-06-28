<?
/*
echo "some text";
exit();
*/
// �������� ���� ������� ��� �������� �������
use Bitrix\Main;
use Bitrix\Main\Entity;

$eventManager = Main\EventManager::getInstance();
$eventManager->addEventHandler("sale", "OnSaleComponentOrderCreated", "xakpl_OnSaleComponentOrderCreated");
//AddEventHandler("sale", "OnSaleComponentOrderCreated", "xakpl_OnSaleComponentOrderCreated");
function xakpl_OnSaleComponentOrderCreated($order, &$arUserResult, $request, &$arParams, &$arResult, &$arDeliveryServiceAll, &$arPaySystemServiceAll){
    $_SESSION['XAKPL_ORDER_BASKET_PRICE'] = $order->getBasket()->getPrice();
}
//OnSaleComponentOrderOneStepComplete
// �������� ���� �������� ����� ��������� ������� ���������
$eventManager->addEventHandler("sale", "onSaleDeliveryServiceCalculate", "xakpl_onSaleDeliveryServiceCalculate");
//AddEventHandler("sale", "onSaleDeliveryServiceCalculate", "xakpl_onSaleDeliveryServiceCalculate");
function xakpl_onSaleDeliveryServiceCalculate($result, $shipment, $deliveryID){
  // �������� id ������ ��������
  if( $deliveryID == 16 ){
        if(isset($_SESSION['XAKPL_ORDER_BASKET_PRICE']) )
        {
            $basketPrice = $_SESSION['XAKPL_ORDER_BASKET_PRICE'];
			// �������� ������ ��������
			//if (region == 216) {}
			// �������� ���� ��������
            $deliveryPrice = $result->getDeliveryPrice();

            $newValue = 350;
            if($basketPrice > 5000){
				// ������� ���� �� �������� �� ������� 100%
                $newValue = 0;
            }
			// ���������� ����� �������� ���� �� ��������
			$shipment->setBasePriceDelivery($newValue, true);

        }
  }
}
\Bitrix\Main\EventManager::getInstance()->addEventHandler('sale', 'onSaleDeliveryServiceCalculate', 'yourHandler');

function yourHandler(\Bitrix\Main\Event $event)
{
    $calcResult = $event->getParameter('RESULT');
    $shipment = $event->getParameter('SHIPMENT');

    // ��������, �������� 200 ������ � ��������� ��������
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
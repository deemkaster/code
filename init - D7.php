<?
//AddEventHandler('sale', 'OnSaleComponentOrderUserResult', "actionResult");
//AddEventHandler('sale', 'OnSaleComponentOrderResultPrepared', "actionResult2");
//AddEventHandler('sale', 'OnBeforeSalePaymentSetField', "actionResult3");
use Bitrix\Main;
use Bitrix\Main\Entity;

$eventManager = Main\EventManager::getInstance();
$eventManager->addEventHandler("sale", "OnBeforeSalePaymentSetField", "actionResult3");

function actionResult3(\Bitrix\Main\Event $event) 
{ 
	$productId = 1;
	$object = $event->getParameter('ENTITY');
	$pay_sys =  (array) $object->getPaymentSystemId();
	$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
		   \Bitrix\Sale\Fuser::getId(),
		   \Bitrix\Main\Context::getCurrent()->getSite()
		);
	if ( $pay_sys[0] == 7) {	
		$fields = [
			'PRODUCT_ID' => $productId,
			'QUANTITY' => 1,
			'LID' => SITE_ID,
		];
		$r = Bitrix\Catalog\Product\Basket::addProduct($fields);	
	}else{
		foreach($basket as $item){
			if($item->getProductId() != $productId){
				continue;
			}
			$item->delete(); 
			$basket->save();
			$basket->refresh();			
		}
	}
}
?>
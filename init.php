<?
define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/log_octo_basket2.txt");
if (!CModule::IncludeModule("sale")) return;

AddEventHandler( 'sale', 'OnBeforeBasketUpdate', 'octoBeforeBasketUpdate' );

function octoBeforeBasketUpdate( $id, $aFields ) {
	
	$settings=octoGet(317); // получаем настройки
	$n=$settings[OCTO_N][VALUE]; 
	$x=$settings[OCTO_X][VALUE];
    if( $aFields[QUANTITY] >= $n ) {
		$x=100-$x;// сколько процентов от старой цены будет новая цена
		$new_qua = floor($aFields[QUANTITY] / $n);//сколько товаров со скидкой
		$new_pr = ($aFields[PRICE]*$x)/100; //новая цена для каждого n товара
		$sum=($aFields[QUANTITY]-$new_qua)*$aFields[PRICE]+$new_qua*$new_pr;//сумма в рублях
		if ($aFields[DISCOUNT_PRICE]=="") //делаем скидку, если на товар нет других скидок
		{
			$aFields[DISCOUNT_PRICE]=$aFields[PRICE]-$sum/$aFields[QUANTITY]; //определяем скидку, равносильную схеме "каждый n за x"
			$aFields[PRICE]=$sum/$aFields[QUANTITY]; // новая цена для товара	
		}
    }
    return true;
}

function octoGet ( $ElementID) //моя стандартная функция для выборки свойст
{
$ob = CIBlockElement::GetList(Array(),
Array("ID"=>$ElementID),false,false,
Array("ID", "IBLOCK_ID", "NAME", "PREVIEW_PICTURE","DETAIL_PICTURE","PREVIEW_TEXT","DETAIL_TEXT", "DETAIL_PAGE_URL", "SECTION_PAGE_URL", "PROPERTY_*"))->GetNextElement();
return array_merge($ob->GetFields(),$ob->GetProperties());
}
?>
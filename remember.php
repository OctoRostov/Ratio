<?
//��� �����
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/" );
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('CHK_EVENT', true);
@set_time_limit(0);
@ignore_user_abort(true);
?>
<?
define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/log_octo.txt");
if (!CModule::IncludeModule("sale")) return;
$first_user=0;
if (isset($_REQUEST[first_user])) $first_user=$_REQUEST[first_user];

$n=2;
if (isset($_REQUEST[n])) $n=$_REQUEST[n]; //������� ������������� ����� �������������� �� ������ ����

if (!isset($_REQUEST[step])) $_REQUEST[step]=1;

AddMessage2Log('����� ������������� � id>'.$first_user,'');
$dbBasketItems = CSaleBasket::GetList( //�������� ��������
	 array("USER_ID" => "ASC",),
	 array("LID" => SITE_ID,
			//"ORDER_ID" => "NULL",
			'>USER_ID' => $first_user,
			'DELAY' => 'Y', //����������
			">=DATE_INSERT" => date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), mktime(0, 0, 0, date("n")-1, date("j"), date("Y")))
			 ),
	 false,
	 false,// array("nTopCount"=>$n),
	 array("ID","USER_ID","PRODUCT_ID")
			 );
$users_basket = array();
while ($arItems = $dbBasketItems->Fetch())  //��������� ������ �� �������� ��� ������� ������������
{
	$users_basket[$arItems[USER_ID]][$arItems[PRODUCT_ID]]=$arItems[PRODUCT_ID];
}

AddMessage2Log('��� ������������ � ����������� ��������� id>'.$first_user." ".print_r($users_basket, true),'');
if(!CModule::IncludeModule("iblock")) return; // ��� CIBlockElement::GetByID
$i=0;
foreach ($users_basket as $key => $user_temp) //��� ������� ����� � ���������. $key - id ������������
{	
	$i++;
	$bask = CSaleOrder::GetList(array("DATE_INSERT" => "ASC"), 
								array( "USER_ID" => $key,
										">=DATE_INSERT" => date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), mktime(0, 0, 0, date("n")-1, date("j"), date("Y"))) //��������� ������ �� ��������� �����
										),
								false,
								false, 
								array()
									);
	while ($orders = $bask->Fetch()) //���������� �� ���� ������� �� �����
	{
		$dbBasketItems2 = CSaleBasket::GetList(
					array(),
					array(
						"LID" => SITE_ID,
					   "ORDER_ID" => $orders["ID"],
					   "PRODUCT_ID" => $users_basket[$key] //�������� �� ������, ������� ���� � ���������� � ���������
					 ),
					 false/*array('USER_ID')*/,
					 false,
					 array("PRODUCT_ID")
				);
	
		while ($arItems2 = $dbBasketItems2->Fetch()) //��� ���� �������, ������� ���� � ���������� � ���������
		{
			unset($users_basket[$key][$arItems2[PRODUCT_ID]]); //������� ����� �� ������
		}
	}
	
	if (count($users_basket[$key]))//���������� ������, ���� ����, � ��� ���������
	{
		$list="";//������ �������
		foreach ($users_basket[$key] as $elementId)
		{
			if($ar_res = CIBlockElement::GetByID($elementId)->GetNext())
				$list.="<a href='".SITE_SERVER_NAME.$ar_res[DETAIL_PAGE_URL]."'>".$ar_res['NAME']."</a><br />";
		}
		$rsUser = CUser::GetByID($key);
		$arUser = $rsUser->Fetch();	
		$fieldsArr = array("���_�������"=>$arUser[NAME]." ".$arUser[LAST_NAME],"EMAIL"=>$arUser[EMAIL], "������_�������"=>$list);
		CEvent::SendImmediate("remember_delay","s1",$fieldsArr);
		AddMessage2Log('�������� ��� ������������['.$key.'], '.$_REQUEST[step].".".$i.' = '.print_r($users_basket[$key], true),'');
	}
	
	if ($i==$n) //���� ���� ���������� �� ��������� ���
	{
		$_REQUEST[step]++;
		header( 'Location: '.$APPLICATION->GetCurPage().'?first_user='.$key.'&step='.$_REQUEST[step].'&n='.$n, true, 307 );// �������� �� ��������� ���
	}		
}
?> 
������ �������� ������,
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>
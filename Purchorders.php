<?php
/* $Revision: 1.28 $ */

$PageSecurity = "B08101";

include('includes/session.inc');
include('cstore_lib/include/mysql.inc.php');
include('cstore_lib/include/CSFunctions.php');

//----運作模式 ----//
$actionCode = $_GET['action_code'];
if ($actionCode==null){
	$actionCode=$_POST['action_code'];
}

// 確認 該使用者 在該作業項 是否有控管權限
if ($actionCode=="AddView") {
	if($_SESSION['SecurityFlag'][$PageSecurity]['add_flag']!='1') {
	?>
	<script language=javascript>
		alert("該使用者沒有此管理權限!!");
		location.href="<?php echo $rootpath.'/Purchorders.php?'.SID.'&action_code=SearchView'?>";
	</script>
	<?php  
	}
}

$title = "採購作業";

//選取模式用
//single,multi,
$choice_mode = $_GET['choice_mode'];
if ($choice_mode==null){
	$choice_mode=$_POST['choice_mode'];
}
if ($choice_mode!=null){
	$title='採購作業';
	$no_header=true;
}

include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('cstore_lib/include/guid.class.php');


if (isset($Errors)) {
	unset($Errors);
}
$Errors = array();

$search_transno = $_GET['search_transno'];
if ($search_transno==null){
	$search_transno=$_POST['search_transno'];
}

$search_inner_transno = $_GET['search_inner_transno'];
if ($search_inner_transno==null){
	$search_inner_transno=$_POST['search_inner_transno'];
}

$search_sys_date = $_GET['search_sys_date'];
if ($search_sys_date==null){
	$search_sys_date=$_POST['search_sys_date'];
}

$search_order_date = $_GET['search_order_date'];
if ($search_order_date==null){
	$search_order_date=$_POST['search_order_date'];
}

$search_supplierid = $_GET['search_supplierid'];
if ($search_supplierid==null){
	$search_supplierid=$_POST['search_supplierid'];
}

//選取模式用-----------------------------------
$selected_key = $_GET['selected_key'];
if ($selected_key==null){
	$selected_key=$_POST['selected_key'];
}
$selected_display = $_GET['selected_display'];
if ($selected_display==null){
	$selected_display=$_POST['selected_display'];
}
//----------------------------------------------


$master_id = $_GET['master_id'];
if ($master_id==null){
  $master_id=$_POST['master_id'];
}

$auto_list = $_GET['auto_list'];
if ($auto_list==null){
  $auto_list=$_POST['auto_list'];
}

$search_result = null;
$last_action_code = $actionCode;
$error_message = null;

if (empty($_REQUEST['identifier'])) {
	/*unique session identifier to ensure that there is no conflict with other order entry sessions on the same machine  */
	$identifier=date('U');
} else {
	$identifier=$_REQUEST['identifier'];
}
if($actionCode != 'AddViewPost' && $actionCode != 'EditViewPost'){
	
	$_SESSION['FormID'.$identifier] = sha1(uniqid(mt_rand(), true));
} else {
	if($_SESSION['master_id'.$identifier]!=''){
		$master_id = $_SESSION['master_id'.$identifier];
	}
}

if ($actionCode=="AddViewPost") {
	
	if($_POST['FormID'] != $_SESSION['FormID'.$identifier] ){
		$actionCode="DetailView";
	} else {
		
		$sql_check = " SELECT orderno FROM purchorders WHERE (1=1) AND deleted = 0 AND orderno = '{$_POST['orderno']}' ";
					
		//echo $sql_check;
		$check_result = DB_query($sql_check,$db);
		if (DB_num_rows($check_result)>0) {
			$_POST['orderno'] = GetNextTransNoEx('18','purchorders','orderno','',$db);
		}
		DB_query('BEGIN', $db);
		//新增
		if ($error_message==null) {
		
			$Guid = new Guid();
			$master_id = $Guid->toString();
			
			$sql_insert =  " INSERT INTO purchorders( "
						." id " 
						." ,orderno "
						." ,purchorder_tpye"
						." ,sys_date"
						." ,order_date "
						." ,supplierid "
						." ,inner_transno "
						." ,outer_transno "
						." ,currency_exchangerate_id "
						." ,rate "
						." ,import_company_categoryid "
						." ,description "
						." ,create_user_id "
						." ,create_user_name "
						." ,create_time "
						." ) " 
						." VALUES ( "
						."  ".SQLStr($master_id)
						." ,".SQLStr($_POST['orderno'])
						." ,".SQLStr($_POST['purchorder_tpye'])
						." ,".SQLStr($_POST['sys_date'])
						." ,".SQLStr($_POST['order_date'])	
						." ,".SQLStr($_POST['supplierid'])	
						." ,".SQLStr($_POST['inner_transno'])	
						." ,".SQLStr($_POST['outer_transno'])	
						." ,".SQLStr($_POST['currency_exchangerate_id'])	
						." ,".SQLStr($_POST['rate'])	
						." ,".SQLStr($_POST['import_company_categoryid'])	
						." ,".SQLStr($_POST['description'])	
						." ,".SQLStr($_SESSION['UserID'])
						." ,".SQLStr($_SESSION['UsersRealName'])
						." ,".SQLStr(date('Y-m-d H:i:s'))
						." ) ";
			// echo $sql_insert;
			DB_query($sql_insert,$db);
			$itemNo = 0;
			for($i=0;$i<count($_POST['stockid']);$i++){	
				$itemNo++;
				$sql_insert =  " INSERT INTO purchorderdetails( "
								." id " 
								." ,purchorders_id "
								." ,itemno"
								." ,stockid"
								." ,production_place_id "
								." ,factory_code "
								." ,purchasing_level_id "
								." ,purchasing_specification_id "
								." ,package_mode_id "
								." ,loc_qty "
								." ,loc_weight "
								." ,unitsofmeasure_id "
								." ,purch_qty "
								." ,rec_qty "
								." ,purch_unitsofmeasure_id "
								." ,outer_price "
								." ,inner_price "
								." ,create_user_id "
								." ,create_user_name "
								." ,create_time "
								." ) " 
								." VALUES ( "
								."  UUID()"
								." ,".SQLStr($master_id)
								." ,".SQLStr($itemNo)
								." ,".SQLStr($_POST['stockid'][$i])
								." ,".SQLStr($_POST['production_place_id'][$i])
								." ,".SQLStr($_POST['factory_code'][$i])
								." ,".SQLStr($_POST['purchasing_level_id'][$i])
								." ,".SQLStr($_POST['purchasing_specification_id'][$i])
								." ,".SQLStr($_POST['package_mode_id'][$i])
								." ,".SQLStr($_POST['loc_qty'][$i])
								." ,".SQLStr($_POST['loc_weight'][$i])
								." ,".SQLStr($_POST['use_units'][$i])
								." ,".SQLStr($_POST['purch_qty'][$i])
								." ,".SQLStr($_POST['rec_qty'][$i])
								." ,".SQLStr($_POST['purch_unitsofmeasure_id'][$i])
								." ,".SQLStr($_POST['outer_price'][$i])
								." ,".SQLStr($_POST['inner_price'][$i])
								." ,".SQLStr($_SESSION['UserID'])
								." ,".SQLStr($_SESSION['UsersRealName'])
								." ,".SQLStr(date('Y-m-d H:i:s'))
								." ) ";
				// echo $sql_insert;
				DB_query($sql_insert,$db);
				
			}
			
			  //給予新ID避免F5重複insert
			  $_SESSION['FormID'.$identifier] = sha1(uniqid(mt_rand(), true));
			  $_SESSION['master_id'.$identifier] = $master_id;
			$actionCode="DetailView";
		}
		DB_query('COMMIT', $db);
	
	}
}
if ($actionCode=="EditViewPost") {
    
	if($_POST['FormID'] != $_SESSION['FormID'.$identifier] ){
		$actionCode="DetailView";
	} else {
		
		DB_query('BEGIN', $db);
		if ($error_message==null) {	
		  
			$sql_update =  " UPDATE purchorders SET "
						." purchorder_tpye = ".SQLStr($_POST['purchorder_tpye'])
						." ,sys_date = ".SQLStr($_POST['sys_date'])
						." ,order_date = ".SQLStr($_POST['order_date'])					
						." ,supplierid = ".SQLStr($_POST['supplierid'])					
						." ,inner_transno = ".SQLStr($_POST['inner_transno'])					
						." ,outer_transno = ".SQLStr($_POST['outer_transno'])					
						." ,currency_exchangerate_id = ".SQLStr($_POST['currency_exchangerate_id'])					
						." ,rate = ".SQLStr($_POST['rate'])					
						." ,import_company_categoryid = ".SQLStr($_POST['import_company_categoryid'])					
						." ,description = ".SQLStr($_POST['description'])					
						." ,modify_user_id =".SQLStr($_SESSION['UserID'])
						." ,modify_user_name =".SQLStr($_SESSION['UsersRealName'])
						." ,modify_time =".SQLStr(date('Y-m-d H:i:s'))
						." WHERE id = ".SQLStr($master_id); 
			// echo $sql_update;
			// exit;
			DB_query($sql_update,$db);
			
			$sql_delete = "
							DELETE FROM purchorderdetails
							WHERE purchorders_id = '{$master_id}'
			";
			DB_query($sql_delete,$db);
			$itemNo = 0;
			for($i=0;$i<count($_POST['stockid']);$i++){	
				$itemNo++;
				$sql_insert =  " INSERT INTO purchorderdetails( "
								." id " 
								." ,purchorders_id "
								." ,itemno"
								." ,stockid"
								." ,production_place_id "
								." ,factory_code "
								." ,purchasing_level_id "
								." ,purchasing_specification_id "
								." ,package_mode_id "
								." ,loc_qty "
								." ,loc_weight "
								." ,unitsofmeasure_id "
								." ,purch_qty "
								." ,rec_qty "
								." ,purch_unitsofmeasure_id "
								." ,outer_price "
								." ,inner_price "
								." ,create_user_id "
								." ,create_user_name "
								." ,create_time "
								." ) " 
								." VALUES ( "
								."  UUID()"
								." ,".SQLStr($master_id)
								." ,".SQLStr($itemNo)
								." ,".SQLStr($_POST['stockid'][$i])
								." ,".SQLStr($_POST['production_place_id'][$i])
								." ,".SQLStr($_POST['factory_code'][$i])
								." ,".SQLStr($_POST['purchasing_level_id'][$i])
								." ,".SQLStr($_POST['purchasing_specification_id'][$i])
								." ,".SQLStr($_POST['package_mode_id'][$i])
								." ,".SQLStr($_POST['loc_qty'][$i])
								." ,".SQLStr($_POST['loc_weight'][$i])
								." ,".SQLStr($_POST['use_units'][$i])
								." ,".SQLStr($_POST['purch_qty'][$i])
								." ,".SQLStr($_POST['rec_qty'][$i])
								." ,".SQLStr($_POST['purch_unitsofmeasure_id'][$i])
								." ,".SQLStr($_POST['outer_price'][$i])
								." ,".SQLStr($_POST['inner_price'][$i])
								." ,".SQLStr($_SESSION['UserID'])
								." ,".SQLStr($_SESSION['UsersRealName'])
								." ,".SQLStr(date('Y-m-d H:i:s'))
								." ) ";
				// echo $sql_insert;
				DB_query($sql_insert,$db);
				
			}
			
			//給予新ID避免F5重複insert
			$_SESSION['FormID'.$identifier] = sha1(uniqid(mt_rand(), true));
		    $_SESSION['master_id'.$identifier] = $master_id;
			$actionCode="DetailView";
		}
		DB_query('COMMIT', $db);
	
	}
}
if ($actionCode=="DeletePost") {
    
	DB_query('BEGIN', $db);
	if ($error_message==null) {
	
		$sql_delete = "
						DELETE FROM purchorderdetails
						WHERE purchorders_id = '{$master_id}'
		";
		DB_query($sql_delete,$db);	

		$sql_delete = "
						DELETE FROM purchorders
						WHERE id = '{$master_id}'
		";
		DB_query($sql_delete,$db);

	}
	$actionCode="SearchViewPost";
	DB_query('COMMIT', $db);
}

//重置分頁功能的function
function ResetPageCtrl(){
	$_SESSION['PurchExpensePageCtrl']=null;
	$PageCtrl = Array();
	$PageCtrl['page_max_rows']=$_SESSION['DisplayRecordsMax'];
	$PageCtrl['sql_search'] = '';
	$PageCtrl['sql_search_count'] = '';
	$PageCtrl['rows_total'] = 0;  
	$PageCtrl['page_total'] = 0;
	$PageCtrl['current_page'] = 1;
	$PageCtrl['start_row'] = 0;
	$PageCtrl['stop_row'] = $PageCtrl['page_max_rows'];  
	$_SESSION['PurchExpensePageCtrl'] = $PageCtrl; 
}

//預設為自動列示
if ($actionCode=="SearchView") {
	$actionCode="SearchViewPost";
	ResetPageCtrl();
}
$disStartPage = true;
$disNextPage = true;
$disLastPage = true;
$disEndPage = true;

if ($actionCode=="SearchViewPost") {	
	//search sql++delete=1 表示被刪除 在查詢的時候只查為0的
    $sql_search = " SELECT * FROM purchorders WHERE (1=1) AND (deleted=0) ";
	
	$sql_search_count = " SELECT count(*) as total FROM purchorders WHERE (1=1) AND (deleted=0) ";
	
	$cond = "";
	
	if ($search_transno!=null) {
		$cond = $cond." AND (orderno LIKE ".SQLStrLike($search_transno).") ";
	}
	
	if ($search_sys_date!=null) {
		$cond = $cond." AND (sys_date = ".SQLStr($search_sys_date).") ";
	}		
	
	if ($search_order_date!=null) {
		$cond = $cond." AND (order_date = ".SQLStr($search_order_date).") ";
	}	
	
	if ($search_supplierid!=null) {
		$cond = $cond." AND (supplierid = ".SQLStr($search_supplierid).") ";
	}	
	
	if ($search_inner_transno!=null) {
		$cond = $cond." AND (inner_transno LIKE ".SQLStrLike($search_inner_transno).") ";
	}
	
	//echo $sql_search_count.$cond;
	$sql_search_count = $sql_search_count . $cond;
	$sql_search_count_result = DB_query($sql_search_count,$db);
	$count_row=DB_fetch_array($sql_search_count_result);
	$rows_total=$count_row['total'];
		
	//page control
	if ($_POST['page_action_code']=='ClearPageCtrl'){
		ResetPageCtrl();
	}
		
	//page setting
		
	//echo $PageCtrl['page_max_rows']."kkk<br>";
	if ( ($_POST['page_action_code']==NULL)&&($_POST['keep_page_action_code']==NULL) ){
		ResetPageCtrl();
	}
	
	$PageCtrl=$_SESSION['PurchExpensePageCtrl'];
	$PageCtrl['sql_search'] = $sql_search_result;
	$PageCtrl['sql_search_count'] = $sql_search_count_result;	
	$PageCtrl['rows_total']=$rows_total;
	
	$PageCtrl['page_total']= ceil($rows_total/$PageCtrl['page_max_rows']);

	if ($_POST['page_action_code']=='StartPage'){
		$PageCtrl['current_page'] = 1;
		$PageCtrl['start_row'] = 0;
		$PageCtrl['stop_row'] = $PageCtrl['page_max_rows'];	
	}
	else if ($_POST['page_action_code']=='NextPage'){
		$PageCtrl['current_page'] = $PageCtrl['current_page'] + 1;
		$PageCtrl['start_row'] = $PageCtrl['start_row'] + $PageCtrl['page_max_rows'];
		$PageCtrl['stop_row'] = $PageCtrl['page_max_rows'];	
	}
	else if ($_POST['page_action_code']=='LastPage'){
		//echo $PageCtrl['start_row']."<br>".$PageCtrl['stop_row']."<br>";
		$PageCtrl['current_page'] = $PageCtrl['current_page'] - 1;
		$PageCtrl['start_row'] = $PageCtrl['start_row'] - $PageCtrl['page_max_rows'];
		$PageCtrl['stop_row'] = $PageCtrl['page_max_rows'];	
	}
	else if ($_POST['page_action_code']=='EndPage'){
		$PageCtrl['current_page'] = $PageCtrl['page_total'];
		$PageCtrl['start_row'] = ($PageCtrl['page_total']-1)*$PageCtrl['page_max_rows'];
		$PageCtrl['stop_row'] = $PageCtrl['page_max_rows']; //剩幾筆
	}
	
	$_SESSION['PurchExpensePageCtrl']=$PageCtrl;

	//button disable control
	if ($PageCtrl['page_total']>1){
	
		if ($PageCtrl['current_page']>1){
			$disStartPage=false;
			$disLastPage=false;
		}
		if ($PageCtrl['current_page']!=$PageCtrl['page_total']){
		
			$disNextPage=false;
			$disEndPage=false;
	
		}
	}
	$orderby = " ORDER BY orderno ";	
	$sql_search = $sql_search . $cond . $orderby . " limit ".$PageCtrl['start_row'].",".$PageCtrl['stop_row'];
		
	//echo $sql_search;
	
	$search_result = DB_query($sql_search,$db);
	//next action change to DetailView
	$actionCode="SearchView";
}

$load_result = null;
$load_row = null;
if ($master_id!=null){
	$sql_load = " SELECT * FROM purchorders WHERE id=".SQLStr($master_id);
	$load_result = DB_query($sql_load,$db);
	$load_row=DB_fetch_array($load_result);
}

?>
<!--sugar style-->
<link media=all href="cstore_lib/sugar_include/calendar-win2k-cold-1.css" type=text/css rel=stylesheet>
<!--日期需要的include-->
<SCRIPT src="cstore_lib/sugar_include/sugar_grp1_yui.js" type=text/javascript></SCRIPT>
<SCRIPT src="cstore_lib/sugar_include/sugar_grp1.js" type=text/javascript></SCRIPT>
<SCRIPT src="cstore_lib/sugar_include/calendar-en.js" type=text/javascript></SCRIPT>
<!--查詢視窗所需要的js-->
<SCRIPT src="cstore_lib/js/check.js" type=text/javascript></SCRIPT>
<link rel="stylesheet" href="jquery-ui-1.10.3.custom/css/start/jquery-ui-1.10.4.custom.css" />
<script src="jquery-ui-1.10.3.custom/js/jquery-1.9.1.js"></script>
<script src="jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.js"></script>

<SCRIPT src="cstore_lib/js/common.js" type=text/javascript></SCRIPT>
<SCRIPT src="cstore_lib/js/dialogs.js" type=text/javascript></SCRIPT>
<SCRIPT src="cstore_lib/js/dialogutil.js" type=text/javascript></SCRIPT>
<!--DataGrid所需要的js-->
<SCRIPT src="cstore_lib/js/DataGrid.js" type=text/javascript></SCRIPT>
<SCRIPT src="cstore_lib/js/fieldscheck.js" type=text/javascript></SCRIPT>
<SCRIPT src="cstore_lib/js/dialogs_ex.js" type=text/javascript></SCRIPT>
<SCRIPT src="cstore_lib/js/dialogutil_ex.js" type=text/javascript></SCRIPT>
<SCRIPT src="cstore_lib/js/quickfields.js" type=text/javascript></SCRIPT>
<SCRIPT src="cstore_lib/ajax/GoodsInfo/getGoodsInfo.js" type="text/javascript"></SCRIPT> 
<script language=javascript>
var oDataGrid1;
$(document).ready(function()
{	
	oDataGrid1 = new DataGrid("datagrid1"); 

});
function doAddView() {
	document.getElementsByName('action_code')[0].value="AddView";
	document.getElementsByName('master_id')[0].value='';
	document.getElementsByName('form1')[0].submit();
}
function doSearchView() {
	clearSearchCond();
	document.getElementsByName('action_code')[0].value="SearchView";
	document.getElementsByName('master_id')[0].value='';
	document.getElementsByName('form1')[0].submit();
}
function doAddViewPost() {
	
	if (!checkMustField()) {
		return;
	}  
	  
	document.getElementsByName('action_code')[0].value="AddViewPost";
	document.getElementsByName('form1')[0].submit();
}
function doEditViewPost() {
	
	if (!checkMustField()) {
		return;
	}  
	document.getElementsByName('action_code')[0].value="EditViewPost";
	document.getElementsByName('form1')[0].submit();
}
function doSearchViewPost() {
	document.getElementsByName('action_code')[0].value="SearchViewPost";
	document.getElementsByName('page_action_code')[0].value="ClearPageCtrl";
	document.getElementsByName('form1')[0].submit();
}
function doEdit(master_id){

    var is_pass = '<?php echo $_SESSION['SecurityFlag'][$PageSecurity]['edit_flag'] ?>';
	if(is_pass!='1') {
		alert("該使用者沒有修改之權限!!");
		return;
	}
	
	//alert(master_id);
	document.getElementsByName('action_code')[0].value="EditView";
	document.getElementsByName('master_id')[0].value=master_id;
	document.getElementsByName('form1')[0].submit();
  
}
function doDetail(master_id){
	//alert(master_id);
	document.getElementsByName('action_code')[0].value="DetailView";
	document.getElementsByName('master_id')[0].value=master_id;
	document.getElementsByName('form1')[0].submit();
  
}
function doDelete(master_id){

    var is_pass = '<?php echo $_SESSION['SecurityFlag'][$PageSecurity]['delete_flag'] ?>';
	if(is_pass!='1') {
		alert("該使用者沒有刪除之權限!!");
		return;
	}
	//alert(master_id);
	if (!confirm('確定刪除此筆記錄!')) {
		return;
	}
	
	document.getElementsByName('action_code')[0].value="DeletePost";
	document.getElementsByName('master_id')[0].value=master_id;
	document.getElementsByName('form1')[0].submit();
  
}

function doBackSearchView(){
	document.getElementsByName('action_code')[0].value="SearchViewPost";
	document.getElementsByName('form1')[0].submit();
}

function clearSearchCond(){
	document.getElementsByName('search_transno')[0].value="";
	document.getElementsByName('search_trandate')[0].value="";
	document.getElementsByName('search_supplierid')[0].value="";
}

function doStartPage(){
	document.getElementsByName('action_code')[0].value="SearchViewPost";
	document.getElementsByName('page_action_code')[0].value="StartPage";
	document.getElementsByName('form1')[0].submit();
}
function doNextPage(){
	document.getElementsByName('action_code')[0].value="SearchViewPost";
	document.getElementsByName('page_action_code')[0].value="NextPage";
	document.getElementsByName('form1')[0].submit();
}
function doLastPage(){
	document.getElementsByName('action_code')[0].value="SearchViewPost";
	document.getElementsByName('page_action_code')[0].value="LastPage";
	document.getElementsByName('form1')[0].submit();
}
function doEndPage(){
	document.getElementsByName('action_code')[0].value="SearchViewPost";
	document.getElementsByName('page_action_code')[0].value="EndPage";
	document.getElementsByName('form1')[0].submit();
}
function checkNumPositEx(theField){
	
	var reg = /^\d+(\.\d+)?$/;
	if(!reg.test(theField.value)){
		alert("請輸入正數!");
		theField.value ='';
		setTimeout(function () {
			theField.focus();
		}, 500);
		return false;
	}
}
function checkNumPosit(theField){
	var reg = /^[+]{0,1}(\d+)$/;
	if(!reg.test(theField.value)){
		alert("請輸入正整數!");
		theField.value ='';
		setTimeout(function () {
			theField.focus();
		}, 500);
		return false;
	}
}

function doloadSql(supplierid){ //廠商編號查詢
	
	 
	 if( supplierid != ""){ //查看是否為數字
	$(function(){
      $.post('./onblur_load_suppliers.php',{supplierid:supplierid},function(data){
		   
		  if(data == "null"){
				alert("查無此廠商");
			    document.getElementById('supplierid').value = "";
			    document.getElementById('suppname').value = "";
		  }
		  else{
			var SQL_data = new Array();
			var SQL_String = data.substr(1,data.length-2);
			var SQL_data = SQL_String.split(",");//去除回傳的頭與尾
			 document.getElementById('supplierid').value = SQL_data[0];
			 document.getElementById('suppname').value = SQL_data[1];
			 form1.submit();
		  }
	  });
    });
	 }

}

function doloadSqlEx(supplierid){ //廠商編號查詢
	
	 
	 if(supplierid != ""){ //查看是否為數字
		$(function(){
		  $.post('./onblur_load_suppliers.php',{supplierid:supplierid},function(data){
			   
			  if(data == "null"){
					alert("查無此廠商");
					document.getElementById('search_supplierid').value = "";
					document.getElementById('search_suppname').value = "";
			  }
			  else{
				var SQL_data = new Array();
				var SQL_String = data.substr(1,data.length-2);
				// alert(SQL_String);
				var SQL_data = SQL_String.split(",");//去除回傳的頭與尾
				 document.getElementById('search_supplierid').value = SQL_data[0];
				 document.getElementById('search_suppname').value = SQL_data[1];
				 // form1.submit();
			  }
		  });
		});
	 }

}

function doloadSql_plural(supplierid){ //廠商編號查詢複數
	var supplierid_arr = new Array();
	var supplierid_arr = supplierid.split(",");
	var check_supplierid = 1;
	for(var i = 0; i < supplierid_arr.length; i++){ //檢查全部是否全為數字
		if(isNaN(supplierid_arr[i])){
			check_supplierid = 0;
			break;
		}
	}
	 if( check_supplierid && supplierid != ""){ 
		$(function(){ 
		
		  $.post('./onblur_load_suppliers_plural.php',{supplierid:supplierid},function(data){ //連接資料庫的PHP
			   
			  if(data == "null"){
				 alert("查無此廠商");
				 document.getElementById('search_suppname').value = "";
				 document.getElementById('search_suppid').value = "";
			  }
			  else{
				
				var SQL_data = new Array();
				var SQL_String = data.substr(1,data.length-2);
				var SQL_data = SQL_String.split("=");//去除回傳的頭與尾
				 document.getElementById('search_suppid').value = SQL_data[0];
				 document.getElementById('search_suppname').value = SQL_data[1];
			  }
		  });
		});
	 }

}


function doChoiceGoods(index){
	
	// if(index > 1){
		// index = index-1;
	// }
	// alert(index);
	var stockid = code = window.showModalDialog("SearchGoodsFrame.php?action_code=SearchView");
	if(stockid!=''){
		doSearchGoods(stockid,index);
	}
}

function doChoiceGoodsOnblour(index){

	// alert(index);
	var stockid = document.getElementsByName('stockid[]')[index].value;
	if(stockid!=''){
		doSearchGoods(stockid,index);
	}
	else{
		DoclearAll(index);
	}
}

function doSearchGoods(stockid,index){
	
	var currency_exchangerate_id = document.getElementsByName('currency_exchangerate_id')[0].value;
	var rate = document.getElementsByName('rate')[0].value;
	
	$.ajax({
			
		url: 'JAGetPurchData.php',
		cache: false,
		async: true,
		type: "POST",
		traditional: true,
		data: { stockid:stockid
			  },
		error: function(xhr) {
		  alert(xhr);
		},
		success: function(response) {
			// alert(response);
			if (response!=undefined && response!='' && response!='false') {
				var msg = jQuery.parseJSON(response);
				document.getElementsByName('stockid[]')[index].value = msg.stockid;
				document.getElementsByName('description_'+(parseInt(index)))[0].innerHTML = msg.description;
				document.getElementsByName('production_place_id_'+(parseInt(index)))[0].innerHTML = msg.place_name;
				document.getElementsByName('production_place_id[]')[index].value = msg.production_place_id;
				document.getElementsByName('factory_code_'+(parseInt(index)))[0].innerHTML = msg.factory_code;
				document.getElementsByName('factory_code[]')[index].value = msg.factory_code;
				document.getElementsByName('purchasing_level_id_'+(parseInt(index)))[0].innerHTML = msg.purchasing_name;
				document.getElementsByName('purchasing_level_id[]')[index].value = msg.purchasing_level_id;
				document.getElementsByName('purchasing_specification_id_'+(parseInt(index)))[0].innerHTML = msg.specification_name;
				document.getElementsByName('purchasing_specification_id[]')[index].value = msg.purchasing_specification_id;
				document.getElementsByName('package_mode_id_'+(parseInt(index)))[0].innerHTML = msg.package_name;
				document.getElementsByName('package_mode_id[]')[index].value = msg.package_mode_id;
				document.getElementsByName('loc_weight_'+(parseInt(index)))[0].innerHTML = msg.loc_weight;
				document.getElementsByName('loc_weight[]')[index].value = msg.loc_weight;		
				document.getElementsByName('loc_qty_'+(parseInt(index)))[0].innerHTML = msg.loc_qty;
				document.getElementsByName('loc_qty[]')[index].value = msg.loc_qty;
				document.getElementsByName('use_units_'+(parseInt(index)))[0].innerHTML = msg.use_units;
				document.getElementsByName('use_units[]')[index].value = msg.use_units;
				// alert( Math.round(msg.stock_purch_price,3) );
				if(currency_exchangerate_id=='a93cacf1-9495-11e8-bdac-bcee7b88fed9'){ //台幣
					//台幣 本國採購單價:採購價,國外採購單價:採購價*匯率
					document.getElementsByName('outer_price[]')[index].value = Math.round( Math.round(msg.stock_purch_price,3)* Math.round(rate,3) ,3); //國外採購單價
					document.getElementsByName('inner_price[]')[index].value = Math.round(msg.stock_purch_price,3); //本國採購單價
				}
				else{
					//非台幣 本國採購單價:採購價/匯率,國外採購單價:採購價
					document.getElementsByName('outer_price[]')[index].value = Math.round(msg.stock_purch_price,3); //國外採購單價
					document.getElementsByName('inner_price[]')[index].value = Math.round(msg.stock_purch_price,3)/Math.round(rate,3); //本國採購單價
				}

			}
			else{				
				alert('無此商品!');
				DoclearAll(index);
			}
		}
	});
		
}
function DoclearAll(index){
	// index = index-1;
	document.getElementsByName('stockid[]')[index].value = '';
	document.getElementsByName('description_'+(parseInt(index)))[0].innerHTML = '';
	document.getElementsByName('production_place_id_'+(parseInt(index)))[0].innerHTML = '';
	document.getElementsByName('production_place_id[]')[index].value = '';
	document.getElementsByName('factory_code_'+(parseInt(index)))[0].innerHTML = '';
	document.getElementsByName('factory_code[]')[index].value = '';
	document.getElementsByName('purchasing_level_id_'+(parseInt(index)))[0].innerHTML = '';
	document.getElementsByName('purchasing_level_id[]')[index].value = '';
	document.getElementsByName('purchasing_specification_id_'+(parseInt(index)))[0].innerHTML = '';
	document.getElementsByName('purchasing_specification_id[]')[index].value = '';
	document.getElementsByName('package_mode_id_'+(parseInt(index)))[0].innerHTML = '';
	document.getElementsByName('package_mode_id[]')[index].value = '';
	document.getElementsByName('loc_weight_'+(parseInt(index)))[0].innerHTML = '';
	document.getElementsByName('loc_weight[]')[index].value = '';	
	document.getElementsByName('loc_qty_'+(parseInt(index)))[0].innerHTML = '';
	document.getElementsByName('loc_qty[]')[index].value ='';
	document.getElementsByName('use_units_'+(parseInt(index)))[0].innerHTML = '';
	document.getElementsByName('unitsofmeasure_id[]')[index].value = '';
	
	document.getElementsByName('purch_qty[]')[index].value ='';
	document.getElementsByName('outer_price[]')[index].value ='';
	document.getElementsByName('inner_price[]')[index].value ='';
	
	
}
</script>

<style type="text/css" rel="stylesheet">

#top{
width:49%;
display:inline;
}

</style>

<?php if ($choice_mode==null) { ?>
	<div id="top" align=left style="font-size:10px">
	<img border=0 src="cstore_lib/sugar_include/images/view.gif">
	<a href="<?php echo $rootpath.'/Purchorders.php?'.SID.'&action_code=SearchView'?>">查詢採購作業</a>
	&nbsp;
	<img border=0 src="cstore_lib/sugar_include/plus.gif">
	<a href="<?php echo $rootpath.'/Purchorders.php?'.SID.'&action_code=AddView'?>">新增採購作業</a>
	</div>
	<!--
	<div id="top" align=right>
	<?php
		$id = urlencode('c.%E5%95%86%E5%93%81%E6%A8%A1%E7%B5%84:%E5%95%86%E5%93%81%E8%A8%88%E9%87%8F%E5%96%AE%E4%BD%8D%E4%B8%BB%E6%AA%94');
	 ?>
	<a href="javascript:showWindow('OpenWikiFrame.php?id=<?php echo $id?>','middle')" ><img border=0 src="cstore_lib/images/help2.png"></a>
	</div>
	-->
<?php } ?>
<hr>

<?php
	if ( ($actionCode=="AddView") || ($actionCode=="EditView") || ($actionCode=="DetailView") ) {
		$disabledFlag = "";
		if ($actionCode=="DetailView") {
			$disabledFlag = "disabled";
		}
?>

<FORM NAME="form1" METHOD='post' action="<?php echo $_SERVER['PHP_SELF'] ?>">
<INPUT type="hidden" name=action_code value="<?php echo $actionCode ?>">
<INPUT type="hidden" name=master_id value="<?php echo $load_row['id']?>">
<INPUT type="hidden" name=auto_list value="false">
<!--條件儲存區-->
<INPUT type="hidden" name=search_transno value="<?php echo $search_transno ?>">
<INPUT type="hidden" name=search_sys_date value="<?php echo $search_sys_date ?>">
<INPUT type="hidden" name=search_order_date value="<?php echo $search_order_date ?>">
<INPUT type="hidden" name=search_inner_transno value="<?php echo $search_inner_transno ?>">
<INPUT type="hidden" name=search_supplierid value="<?php echo $search_supplierid ?>">
<center>
<h4>
採購作業 - 
<?php
	if ($actionCode=="AddView")
		echo "新增";
	else if ($actionCode=="EditView")
		echo "修改";
	else if ($actionCode=="DetailView")
		echo "明細";
?>	

</h4>

<table border=1 width="85%" cellSpacing=0>
	<tr>
		<td class=tableheader width=7%>採購單號:</td>
		<td class=EvenTableRows width=15%>
		<?php 
			$_POST['orderno'] = $load_row['orderno'];
			
			if($_POST['orderno']=='') {
				$_POST['orderno'] = GetNextTransNoEx('18','purchorders','orderno','',$db);
			}	   
		?>   
		<span><?php echo $_POST['orderno']; ?></span>
		<input type=hidden name="orderno" value="<?php echo $_POST['orderno'] ?>">
		</td>
		
		<td class=tableheader width=7%>採購類別:</td>
			<td class=EvenTableRows width=15%>
			<?php
				if($_POST['purchorder_tpye']=='') {
					$_POST['purchorder_tpye'] = $load_row['purchorder_tpye'];
				}	
			?>
			<?php if($actionCode!="DetailView") { ?>   
				<select name="purchorder_tpye" must=1 msg='採購類別'>
				<?php echo getSelectHtmlEx1($db,'purchorder_tpye','id','purchorder_tpye_name'," (id='1') ",$_POST['purchorder_tpye'],NULL,'')?>
				</select>
			<?php } else { echo getColumnValue($db, 'purchorder_tpye', 'purchorder_tpye_name', "id='{$_POST['purchorder_tpye']}' ");
			} ?>
		</td>	
		
		<td class=tableheader width=7%>入系統日:</td>
		<td class=EvenTableRows width=15%>
		  <?php	 
			if ($_POST['sys_date']=='') {
			   $_POST['sys_date'] = $load_row['sys_date'];
			}
			if($_POST['sys_date'] == '') $_POST['sys_date'] = date('Y-m-d');
		  ?>
		  <?php if ($actionCode!="DetailView") {?>
			<INPUT id=sys_date title="" must=1 msg="入系統日" maxLength=10 size=10 name=sys_date autocomplete="on" value="<?php echo $_POST['sys_date'] ?>" >
				  <IMG  id=sys_date_trigger alt="日期輸入" src="cstore_lib/images/jscalendar.gif" align=absMiddle border=0>
				  <SCRIPT language=javascript type=text/javascript>
					Calendar.setup ({
					  inputField : "sys_date",
					  daFormat : "%Y-%m-%d",
					  button : "sys_date_trigger",
					  singleClick : true,
					  dateStr : "",
					  step : 1
					}
					);
				  </SCRIPT>
		  <?php } else {?>
			<span><?php echo $_POST['sys_date'] ?></span>	
		  <?php } ?>
		</td>	

		<td class=tableheader width=7%>採購日期:</td>
		<td class=EvenTableRows width=15%>
		  <?php	 
			if ($_POST['order_date']=='') {
			   $_POST['order_date'] = $load_row['order_date'];
			}
			if($_POST['order_date'] == '') $_POST['order_date'] = date('Y-m-d');
		  ?>
		  <?php if ($actionCode!="DetailView") {?>
			<INPUT id=order_date title="" must=1 msg="採購日期" maxLength=10 size=10 name=order_date autocomplete="on" value="<?php echo $_POST['order_date'] ?>" >
				  <IMG  id=order_date_trigger alt="日期輸入" src="cstore_lib/images/jscalendar.gif" align=absMiddle border=0>
				  <SCRIPT language=javascript type=text/javascript>
					Calendar.setup ({
					  inputField : "order_date",
					  daFormat : "%Y-%m-%d",
					  button : "order_date_trigger",
					  singleClick : true,
					  dateStr : "",
					  step : 1
					}
					);
				  </SCRIPT>
		  <?php } else {?>
			<span><?php echo $_POST['order_date'] ?></span>	
		  <?php } ?>
		</td>	
	</tr>
	
	<tr>
		<td class=tableheader width=7%>供應商代碼:</td>
		<td class=EvenTableRows width=15%>
		<?php
			if($_POST['supplierid']=='') {
				$_POST['supplierid'] = $load_row['supplierid'];
			}		
		?>
		<?php if($actionCode!="DetailView") { ?> 
			<INPUT type="text" must=1 msg='供應商代碼' onblur="doloadSql(this.value)" name="supplierid" SIZE=20 VALUE="<?php echo $_POST['supplierid']?>">
			<INPUT type="hidden" name="suppname" SIZE=20 VALUE="<?php echo $_POST['suppname'] ?>">
			<INPUT TYPE=button NAME="btnChoiceBranch" onclick="doChoiceSupp('supplierid','suppname');" VALUE="查詢">
			<INPUT TYPE=button NAME="btnClearBranch" onclick="doClearChoice('supplierid','suppname');" VALUE="清除">
			
		<?php } else { ?>
		<span><?php echo $_POST['supplierid'];?></span> 
		<?php } ?>
		</td>	
		
		<td class=tableheader width=7%>公司訂單編號:</td>
		<td class=EvenTableRows width=15%>
		  <?php	 
			if ($_POST['inner_transno']=='') {
			   $_POST['inner_transno'] = $load_row['inner_transno'];
			}
		  ?>
		  <?php if ($actionCode!="DetailView") {?>
			<input style="text-align=right;" type="text" must=1 msg='公司訂單編號' name="inner_transno" value="<?php echo $_POST['inner_transno'] ?>">
		  <?php } else {?>
			<span><?php echo $_POST['inner_transno'] ?></span>	
		  <?php } ?>
		</td>	

		<td class=tableheader width=7%>國外訂單編號:</td>
		<td class=EvenTableRows width=15% colspan=3>
		  <?php	 
			if ($_POST['outer_transno']=='') {
			   $_POST['outer_transno'] = $load_row['outer_transno'];
			}
		  ?>
		  <?php if ($actionCode!="DetailView") {?>
			<input style="text-align=right;" type="text" name="outer_transno" value="<?php echo $_POST['outer_transno'] ?>">
		  <?php } else {?>
			<span><?php echo $_POST['outer_transno'] ?></span>	
		  <?php } ?>
		</td>	
	</tr>

	<tr>
		<td class=tableheader width=7%>幣別:</td>
		<td class=EvenTableRows width=15%>
			<?php
				if($_POST['currency_exchangerate_id']=='') {
					$_POST['currency_exchangerate_id'] = $load_row['currency_exchangerate_id'];
				}	
			?>
			<?php if($actionCode!="DetailView") { ?>   
				<select name="currency_exchangerate_id" >
				<?php echo getSelectHtmlEx1($db,'currency_exchangerate','id','currency_name'," (1=1) ",$_POST['currency_exchangerate_id'],NULL,'')?>
				</select>
			<?php } else { echo getColumnValue($db,'currency_exchangerate','currency_name',"(id ='".$_POST['currency_exchangerate_id']."'  )");
			} ?>
		</td>	
		
		<td class=tableheader width=7%>匯率:</td>
		<td class=EvenTableRows width=15%>
		  <?php	 
			if ($_POST['rate']=='') {
			   $_POST['rate'] = $load_row['rate'];
			}
		  ?>
		  <?php if ($actionCode!="DetailView") {?>
			<input style="text-align=right;" type="text" must=1 msg='匯率' name="rate" value="<?php echo $_POST['rate'] ?>">
		  <?php } else {?>
			<span><?php echo $_POST['rate'] ?></span>	
		  <?php } ?>
		</td>	

		<td class=tableheader width=7%>進口公司:</td>
		<td class=EvenTableRows width=15% colspan=3>
			<?php
				if($_POST['import_company_categoryid']=='') {
					$_POST['import_company_categoryid'] = $load_row['import_company_categoryid'];
				}	
			?>
			<?php if($actionCode!="DetailView") { ?>   
				<select name="import_company_categoryid" must=1 msg='採購類別'>
				<?php echo getSelectHtmlEx1($db,'import_company','categoryid','categorydescription'," (1=1) ",$_POST['import_company_categoryid'],NULL,'')?>
				</select>
			<?php } else { echo $_POST['import_company_categoryid'];
			} ?>
		</td>
	</tr>
	<tr>
		<td class=tableheader width=7%>備註:</td>
		<td class=EvenTableRows colspan="7" width=15%>
		<?php	 
			if ($_POST['description']=='') {
				$_POST['description'] = $load_row['description'];
			}	
		?>
		<?php if ($actionCode!="DetailView") {?>
			<textarea <?php echo $disabledFlag ?> name="description" maxlength="255" style="width:100%; height:3em;"><?php echo trim($_POST['description'])?></textarea>
		<?php } else {?>
			<span><?php echo HtmlTextAreaStr($_POST['description']) ?></span>
		<?php } ?>
		</td>
	</tr>
	
 
 <?php if ($actionCode=='DetailView') {	?>
	<tr>
		<td class=CreateInfoHead width=22% colspan=2>建立人員:</td>
		<td class=CreateInfoData width=22% colspan=2>
		<span><?php echo $load_row['create_user_name'] ?></span>
		</td>
   
		<td class=CreateInfoHead width=22% colspan=2>建立時間:</td>
		<td class=CreateInfoData width=22% colspan=2>
		<span><?php echo $load_row['create_time'] ?></span>
		</td>
	</tr> 
 
	<tr>
		<td class=CreateInfoHead width=22% colspan=2>修改人員:</td>
		<td class=CreateInfoData width=22% colspan=2>
		<span><?php echo HtmlStr($load_row['modify_user_name']) ?></span>
		</td>
		<td class=CreateInfoHead width=22% colspan=2>修改時間:</td>
		<td class=CreateInfoData width=22% colspan=2>
		<span><?php echo HtmlStr($load_row['modify_time']) ?></span>
		</td>
	</tr> 
 <?php } //在Detail頁顯示建立修改資訊?>
 
</table>

<br>
<br>

<?php if(true) { 
?>

<table id="datagrid1" border=1 style="overflow:auto;" width="100%" cellSpacing=0>
	<thead>
		<tr>
			<th style="text-align:left;font-size:14px" colspan=16>採購明細
			</th>
		</tr>  
		<tr>
			<th>項次</th>
			<th>商品代碼</th>
			<th>商品名稱</th>
			<th>國別</th>
			<th>廠號</th>
			<th>等級</th>
			<th>規格</th>
			<th>包裝方式</th>	
			<th>現有庫存量</th>			
			<th>庫存總重量</th>
			<th>庫存單位</th>
			<th>採購重(數)量</th>
			<th>採購單位</th>
			<th>國外採購單價</th>
			<th>本國採購單價</th>
			<?php if($actionCode!="DetailView"){ ?>
			<th><input type=button tabindex="-1" name="btnAddShipReceived" value=新增 onclick="javascript:oDataGrid1.add_row();">  </th>
			<?php }else{ ?>
			<th>  </th>
			<?php } ?>
		</tr>
	</thead>
	<?php if($actionCode=="AddView"){ ?>
	<tbody>	
		<tr>
			<td style="text-align:center;">
				<span><?php echo ++$temp_index ?></span>
			</td> 
			<td style="text-align=center;">
				 <input type=text size=14 maxlength=20 id="stockid" name="stockid[]" onblur="javascript:doChoiceGoodsOnblour( oDataGrid1.getRowIndex(this) )" value="" >
				 <input type=button name=btnChoiceGoods2 value="查詢" onclick="javascript:doChoiceGoods(oDataGrid1.getRowIndex(this));">	
				 <input type=button name=btnChoiceGoods value="清除" onclick="javascript:DoclearAll(oDataGrid1.getRowIndex(this));">	
				
			</td>	
			<td style="text-align=left;">
				<span name="description_<?php echo ($temp_index-1) ?>" id="description_<?php echo ($temp_index-1) ?>">
					<?php echo ''; ?>
				</span>
			</td>
			<td style="text-align=left;">
				<span name="production_place_id_<?php echo ($temp_index-1) ?>" id="production_place_id_<?php echo ($temp_index-1) ?>">
					<?php echo ''; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="production_place_id[]" value="<?php echo $_POST['production_place_id'][$temp_index] ?>">
			</td>
			<td style="text-align=left;">
				<span name="factory_code_<?php echo ($temp_index-1) ?>" id="factory_code_<?php echo ($temp_index-1) ?>">
					<?php echo ''; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="factory_code[]" value="<?php echo $_POST['factory_code'][$temp_index] ?>">
			</td>
			<td style="text-align=left;">
				<span name="purchasing_level_id_<?php echo ($temp_index-1) ?>" id="purchasing_level_id_<?php echo ($temp_index-1) ?>">
					<?php echo ''; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="purchasing_level_id[]" value="<?php echo $_POST['purchasing_level_id'][$temp_index] ?>">
			</td>
			<td style="text-align=left;">
				<span name="purchasing_specification_id_<?php echo ($temp_index-1) ?>" id="purchasing_specification_id_<?php echo ($temp_index-1) ?>">
					<?php echo ''; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="purchasing_specification_id[]" value="<?php echo $_POST['purchasing_specification_id'][$temp_index] ?>">
			</td>
			<td style="text-align=left;">
				<span name="package_mode_id_<?php echo ($temp_index-1) ?>" id="package_mode_id_<?php echo ($temp_index-1) ?>">
					<?php echo ''; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="package_mode_id[]" value="<?php echo $_POST['package_mode_id'][$temp_index] ?>">
			</td>
			<td style="text-align=right;">
				<span name="loc_qty_<?php echo ($temp_index-1) ?>" id="loc_qty_<?php echo ($temp_index-1) ?>">
					<?php echo ''; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="loc_qty[]" value="<?php echo $_POST['loc_qty'][$temp_index] ?>">
			</td>			
			<td style="text-align=right;">
				<span name="loc_weight_<?php echo ($temp_index-1) ?>" id="loc_weight_<?php echo ($temp_index-1) ?>">
					<?php echo ''; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="loc_weight[]" value="<?php echo $_POST['loc_weight'][$temp_index] ?>">
			</td>		
			<td style="text-align=left;">
				<span name="use_units_<?php echo ($temp_index-1) ?>" id="use_units_<?php echo ($temp_index-1) ?>">
					<?php echo ''; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="use_units[]" value="<?php echo $_POST['unitsofmeasure_id'][$temp_index] ?>">
			</td>	
			<td style="text-align=right;">
				<input type="text" must=1 msg='採購重(數)量' style="text-align=right;" name="purch_qty[]" value="<?php echo $_POST['purch_qty'][$temp_index] ?>" onchange="checkNumPositEx(this)">
			</td>	
			<td style="text-align=center;">
					<select name="purch_unitsofmeasure_id[]"  must=1 msg='採購單位'>
						<?php echo getSelectHtmlEx1($db,'unitsofmeasure','unit_name','unit_name'," (1=1) ",$_POST['purch_unitsofmeasure_id'][$temp_index],'---------') ?>
					</select>
			</td>
			<td style="text-align=right;">
				<input type="text"  must=1 msg='國外採購單價' style="text-align=right;" name="outer_price[]" value="<?php echo $_POST['outer_price'][$temp_index] ?>" onchange="checkNumPositEx(this)">
			</td>	
			<td style="text-align=right;">
				<input type="text"  must=1 msg='本國採購單價' style="text-align=right;" name="inner_price[]" value="<?php echo $_POST['inner_price'][$temp_index] ?>" onchange="checkNumPositEx(this)">
			</td>				
		   <td class=OddTableRows colspan=1 style="font-size:14px;text-align:center">
				<input type=button tabindex="-1" name=xxx value=刪除 onClick="javascript:oDataGrid1.delete_row(this);">
		   </td> 
		</tr>
	</tbody>
	<?php }else if($actionCode=="EditView"){ ?>	
			<tbody>	
			<?php 
					$sql_detail = "
									SELECT   id
											,purchorders_id
											,itemno
											,stockid
											,production_place_id
											,factory_code
											,purchasing_level_id
											,purchasing_specification_id
											,package_mode_id
											,loc_qty
											,loc_weight
											,unitsofmeasure_id
											,purch_qty
											,rec_qty
											,purch_unitsofmeasure_id
											,outer_price
											,inner_price
									FROM purchorderdetails
									WHERE purchorders_id = '{$master_id}'
									ORDER BY itemNo ASC
					";
					$rs_detail = DB_query($sql_detail,$db);
					while($row_detail=DB_fetch_array($rs_detail)){
			?>
			<tr>
			<td style="text-align:center;">
				<span><?php echo ++$temp_index ?></span>
			</td> 
			<td style="text-align=center;">
				 <input type=text size=14 maxlength=20 id="stockid" name="stockid[]" onblur="javascript:doChoiceGoodsOnblour( oDataGrid1.getRowIndex(this) )" value="<?php echo $row_detail['stockid']; ?>" >
				 <input type=button name=btnChoiceGoods2 value="查詢" onclick="javascript:doChoiceGoods(oDataGrid1.getRowIndex(this));">	
				 <input type=button name=btnChoiceGoods value="清除" onclick="javascript:DoclearAll(oDataGrid1.getRowIndex(this));">	
				
			</td>	
			<td style="text-align=left;">
				<span name="description_<?php echo ($temp_index-1) ?>" id="description_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'stockmaster','description',"(stockid ='".$row_detail['stockid']."'  )"); ?>
				</span>
			</td>
			<td style="text-align=left;">
				<span name="production_place_id_<?php echo ($temp_index-1) ?>" id="production_place_id_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'production_place','place_name',"(id ='".$row_detail['production_place_id']."'  )"); ?>
				</span>
				<input type="hidden" style="text-align=right;" name="production_place_id[]" value="<?php echo $row_detail['production_place_id']; ?>">
			</td>
			<td style="text-align=left;">
				<span name="factory_code_<?php echo ($temp_index-1) ?>" id="factory_code_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['factory_code']; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="factory_code[]" value="<?php echo $row_detail['factory_code']; ?>">
			</td>
			<td style="text-align=left;">
				<span name="purchasing_level_id_<?php echo ($temp_index-1) ?>" id="purchasing_level_id_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'purchasing_level','purchasing_name',"(id ='".$row_detail['purchasing_level_id']."'  )"); ?>
				</span>
				<input type="hidden" style="text-align=right;" name="purchasing_level_id[]" value="<?php echo $row_detail['purchasing_level_id']; ?>">
			</td>
			<td style="text-align=left;">
				<span name="purchasing_specification_id_<?php echo ($temp_index-1) ?>" id="purchasing_specification_id_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'purchasing_specification','specification_name',"(id ='".$row_detail['purchasing_specification_id']."'  )"); ?>
				</span>
				<input type="hidden" style="text-align=right;" name="purchasing_specification_id[]" value="<?php echo $row_detail['purchasing_specification_id']; ?>">
			</td>
			<td style="text-align=left;">
				<span name="package_mode_id_<?php echo ($temp_index-1) ?>" id="package_mode_id_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'package_mode','package_name',"(id ='".$row_detail['package_mode_id']."'  )"); ?>
				</span>
				<input type="hidden" style="text-align=right;" name="package_mode_id[]" value="<?php echo $row_detail['package_mode_id']; ?>">
			</td>
			<td style="text-align=right;">
				<span name="loc_qty_<?php echo ($temp_index-1) ?>" id="loc_qty_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['loc_qty']; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="loc_qty[]" value="<?php echo $row_detail['loc_qty']; ?>">
			</td>			
			<td style="text-align=right;">
				<span name="loc_weight_<?php echo ($temp_index-1) ?>" id="loc_weight_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['loc_weight']; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="loc_weight[]" value="<?php echo $row_detail['loc_weight']; ?>">
			</td>		
			<td style="text-align=center;">
				<span name="use_units_<?php echo ($temp_index-1) ?>" id="use_units_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['unitsofmeasure_id']; ?>
				</span>
				<input type="hidden" style="text-align=right;" name="use_units[]" value="<?php echo $row_detail['unitsofmeasure_id']; ?>">
			</td>	
			<td style="text-align=right;">
				<input type="text"  must=1 msg='採購重(數)量' style="text-align=right;" name="purch_qty[]" value="<?php echo $row_detail['purch_qty']; ?>" onchange="checkNumPositEx(this)">
			</td>	
			<td style="text-align=center;">
					<select name="purch_unitsofmeasure_id[]" must=1 msg='採購單位'>
						<?php echo getSelectHtmlEx1($db,'unitsofmeasure','unit_name','unit_name'," (1=1) ",$row_detail['purch_unitsofmeasure_id'],'---------') ?>
					</select>
			</td>
			<td style="text-align=right;">
				<input type="text" must=1 msg='國外採購單價' style="text-align=right;" name="outer_price[]" value="<?php echo $row_detail['outer_price'] ?>" onchange="checkNumPositEx(this)">
			</td>	
			<td style="text-align=right;">
				<input type="text" must=1 msg='本國採購單價' style="text-align=right;" name="inner_price[]" value="<?php echo $row_detail['inner_price'] ?>" onchange="checkNumPositEx(this)">
			</td>				
		   <td class=OddTableRows colspan=1 style="font-size:14px;text-align:center">
				<input type=button tabindex="-1" name=xxx value=刪除 onClick="javascript:oDataGrid1.delete_row(this);">
		   </td> 
		</tr>
				<?php 
					}
				?>
		</tbody>
	<?php }else if($actionCode=="DetailView"){ ?>
			<tbody>	
			<?php 
					$sql_detail = "
									SELECT   id
											,purchorders_id
											,itemno
											,stockid
											,production_place_id
											,factory_code
											,purchasing_level_id
											,purchasing_specification_id
											,package_mode_id
											,loc_qty
											,loc_weight
											,unitsofmeasure_id
											,purch_qty
											,rec_qty
											,purch_unitsofmeasure_id
											,outer_price
											,inner_price
									FROM purchorderdetails
									WHERE purchorders_id = '{$master_id}'
									ORDER BY itemNo ASC
					";
					$rs_detail = DB_query($sql_detail,$db);
					while($row_detail=DB_fetch_array($rs_detail)){
			?>
			<tr>
			<td style="text-align:center;">
				<span><?php echo ++$temp_index ?></span>
			</td> 
			<td style="text-align=left;">
				<span name="description_<?php echo ($temp_index-1) ?>" id="description_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['stockid']; ?>
				</span>
			</td>	
			<td style="text-align=left;">
				<span name="description_<?php echo ($temp_index-1) ?>" id="description_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'stockmaster','description',"(stockid ='".$row_detail['stockid']."'  )"); ?>
				</span>
			</td>
			<td style="text-align=left;">
				<span name="production_place_id_<?php echo ($temp_index-1) ?>" id="production_place_id_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'production_place','place_name',"(id ='".$row_detail['production_place_id']."'  )"); ?>
				</span>
				
			</td>
			<td style="text-align=left;">
				<span name="factory_code_<?php echo ($temp_index-1) ?>" id="factory_code_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['factory_code']; ?>
				</span>
				
			</td>
			<td style="text-align=left;">
				<span name="purchasing_level_id_<?php echo ($temp_index-1) ?>" id="purchasing_level_id_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'purchasing_level','purchasing_name',"(id ='".$row_detail['purchasing_level_id']."'  )"); ?>
				</span>
				
			</td>
			<td style="text-align=left;">
				<span name="purchasing_specification_id_<?php echo ($temp_index-1) ?>" id="purchasing_specification_id_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'purchasing_specification','specification_name',"(id ='".$row_detail['purchasing_specification_id']."'  )"); ?>
				</span>
				
			</td>
			<td style="text-align=left;">
				<span name="package_mode_id_<?php echo ($temp_index-1) ?>" id="package_mode_id_<?php echo ($temp_index-1) ?>">
					<?php echo getColumnValue($db,'package_mode','package_name',"(id ='".$row_detail['package_mode_id']."'  )"); ?>
				</span>
				
			</td>
			<td style="text-align=right;">
				<span name="loc_qty_<?php echo ($temp_index-1) ?>" id="loc_qty_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['loc_qty']; ?>
				</span>
				
			</td>			
			<td style="text-align=right;">
				<span name="loc_weight_<?php echo ($temp_index-1) ?>" id="loc_weight_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['loc_weight']; ?>
				</span>
				
			</td>		
			<td style="text-align=center;">
				<span name="use_units_<?php echo ($temp_index-1) ?>" id="use_units_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['unitsofmeasure_id']; ?>
				</span>
				
			</td>	
			<td style="text-align=right;">
				<span name="use_units_<?php echo ($temp_index-1) ?>" id="use_units_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['purch_qty']; ?>
				</span>
			</td>	
			<td style="text-align=center;">
				<span name="use_units_<?php echo ($temp_index-1) ?>" id="use_units_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['purch_unitsofmeasure_id']; ?>
				</span>
			</td>
			<td style="text-align=right;">
				<span name="use_units_<?php echo ($temp_index-1) ?>" id="use_units_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['outer_price']; ?>
				</span>
			</td>	
			<td style="text-align=right;">
				<span name="use_units_<?php echo ($temp_index-1) ?>" id="use_units_<?php echo ($temp_index-1) ?>">
					<?php echo $row_detail['inner_price']; ?>
				</span>
			</td>				
		   <td class=OddTableRows colspan=1 style="font-size:14px;text-align:center">
				
		   </td> 
		</tr>
				<?php 
					}
				?>
		</tbody>
	<?php } ?>	
</table>
<?php }
?>

<?php
if ($actionCode=="AddView") {
?>
	<input class=button_action type=button name=btnAdd value="儲存" onclick="doAddViewPost()">&nbsp;
<?php
}
?>

<?php
if ($actionCode=="EditView") {
?>
	<input class=button_action type=button name=btnAdd value="儲存" onclick="doEditViewPost()">&nbsp;
<?php
}
?>

<?php
if ($actionCode=="DetailView") {
?>

<?php
}
else {
?>
	<input class=button_action type=reset name=btnReset value="重置">&nbsp;
<?php
}
?>

<?php
if ( ($actionCode=="DetailView") ) {
?>
	<input class=button_action type=button name=btnAdd value="修改這一筆" onclick="doEdit('<?php echo $load_row['id']?>')">&nbsp;
<?php
}
?>

<?php
if ( (($actionCode=="DetailView") || ($actionCode=="EditView")) && ($last_action_code!="AddViewPost") ) {
?>
	<input class=button_action type=button name=btnAdd value="返回查詢頁" onclick="doBackSearchView()">&nbsp;
<?php
}
?>

<?php
if ($last_action_code=="AddViewPost") {
?>
	<input class=button_action type=button name=btnAdd value="繼續新增" onclick="doAddView()">&nbsp;
<?php
}
?>

</center>
</FORM>
<hr>
<?php
} // end of AddNewView  begin SearchView
else if ($actionCode=="SearchView") {
?>
<center>
<h4>
<?php echo $title.' - '.'查詢' ?>
</h4>
</center> 

<FORM NAME=form1 ACTION="<?php echo $_SERVER['PHP_SELF'].'?'.SID?>" METHOD=POST>
	<INPUT type="hidden" name=action_code value="<?php echo $actionCode ?>">
	<INPUT type="hidden" name=master_id value=''>
	<INPUT type="hidden" name=page_action_code>

	<?php
		$keep_page_action_code = $_POST['page_action_code'];
		if ($keep_page_action_code==null) {
			$keep_page_action_code = $_POST['keep_page_action_code'];
		}
	?>
	<INPUT type="hidden" name=keep_page_action_code value="<?php echo $keep_page_action_code?>">

	<!--選取模式用-->
	<INPUT type="hidden" name=choice_mode value='<?php echo $choice_mode?>'>
	<INPUT type="hidden" name=selected_key value='<?php echo $selected_key?>'>
	<INPUT type="hidden" name=selected_display value='<?php echo $selected_display?>'>
	<!---->

	<center>
	<table border=1 CELLPADDING=0>
		<tr>
			<td class=tableheader>採購單號: </td>
			<td class=OddTableRows>
				<input type="text" name="search_transno" size="20" maxlength="45" value="<?php echo $_POST['search_transno']?>">
			</td>		

			<td class=tableheader>入系統日: </td>
			<td class=OddTableRows>
				<INPUT id="search_sys_date" title="" maxLength=10  case_type="date" size=10 name="search_sys_date" autocomplete="on" value="<?php echo $_POST['search_sys_date'] ?>" >
								  <IMG  id=search_sys_date_trigger  case_type="date" alt="日期輸入" src="cstore_lib/images/jscalendar.gif" align=absMiddle border=0>
								  <SCRIPT language=javascript type="text/javascript">
									Calendar.setup ({
									  inputField : "search_sys_date",
									  daFormat : "%Y-%m-%d",
									  button : "search_sys_date_trigger",
									  singleClick : true,
									  dateStr : "",
									  step : 1
									}
									);
								  </SCRIPT>
			</td>
			
			<td class=tableheader>採購日期: </td>
			<td class=OddTableRows>
				<INPUT id="search_order_date" title="" maxLength=10  case_type="date" size=10 name="search_order_date" autocomplete="on" value="<?php echo $_POST['search_order_date'] ?>" >
								  <IMG  id=search_order_date_trigger  case_type="date" alt="日期輸入" src="cstore_lib/images/jscalendar.gif" align=absMiddle border=0>
								  <SCRIPT language=javascript type="text/javascript">
									Calendar.setup ({
									  inputField : "search_order_date",
									  daFormat : "%Y-%m-%d",
									  button : "search_order_date_trigger",
									  singleClick : true,
									  dateStr : "",
									  step : 1
									}
									);
								  </SCRIPT>
			</td>
		</tr>
		<tr>	
			<td class=tableheader>供應商: </td>
			<td class=OddTableRows>
				<INPUT type="text" must=1 msg='供應商代碼' onblur="doloadSqlEx(this.value)" name="search_supplierid" SIZE=20 VALUE="<?php echo $_POST['search_supplierid']?>">
			<INPUT type="hidden" name="search_suppname" SIZE=20 VALUE="<?php echo $_POST['search_suppname'] ?>">
			<INPUT TYPE=button NAME="btnChoiceBranch" onclick="doChoiceSupp('search_supplierid','search_suppname')" VALUE="查詢">
			<INPUT TYPE=button NAME="btnClearBranch" onclick="doClearChoice('search_supplierid','search_suppname');" VALUE="清除">
			</td>	
			
			<td class=tableheader>公司訂單編號: </td>
			<td class=OddTableRows colspan=3>
				<input type="text" name="search_inner_transno" size="20" maxlength="45" value="<?php echo $_POST['search_inner_transno']?>">
			</td>		

		</tr>
	</table>
	<br>
	<INPUT class="button_action" TYPE=button NAME='btnSearch' VALUE='搜尋' onclick="javascript:doSearchViewPost();">&nbsp;
	<INPUT class="button_action" TYPE=button NAME='btnSearch' VALUE='清除' onclick="javascript:clearSearchCond();">
	</center>
</FORM>
<hr>

<TABLE CELLPADDING=2 BORDER=1 width=100%>
	<span>
	<td class=OddTableRows colspan=7 style=text-align:right>

	<?php
	$disableFlag = "disabled";
	$disableImg = "_off";
	if (!$disStartPage){
		$disableFlag="";
		$disableImg=""; 
	}
	?>

	<button <?php echo $disableFlag?> title='起頭' class='button' onclick="doStartPage()">
	<img src='cstore_lib/sugar_include/start<?php echo $disableImg?>.gif' alt='起頭' align='absmiddle' border='0' width='13' height='11'>
	</button>
	<?php
	$disableFlag = "disabled";
	$disableImg = "_off";
	if (!$disLastPage){    
		$disableFlag="";
		$disableImg="";  
	}
	?>

	<button <?php echo $disableFlag?> class='button' title='上頁' onclick="doLastPage()">
	<img src='cstore_lib/sugar_include/previous<?php echo $disableImg?>.gif' alt='上頁' align='absmiddle' border='0' width='10' height='11'>
	</button>


	<?php
		$PageCtrl = $_SESSION['PurchExpensePageCtrl'];
	?>

	<span class='pageNumbers'>總筆數:<?php echo $PageCtrl['rows_total']?>&nbsp;<?php echo "(第".$PageCtrl['current_page']."頁 - 共".$PageCtrl['page_total']."頁)" ?></span>

	<?php
	$disableFlag = "disabled";
	$disableImg = "_off";
	if (!$disNextPage){    
		$disableFlag="";
		$disableImg="";
	}
	?>

	<button <?php echo $disableFlag?> class='button' title='下頁' onclick="doNextPage()">
	<img src='cstore_lib/sugar_include/next<?php echo $disableImg?>.gif' alt='下頁' align='absmiddle' border='0' width='10' height='11'>
	</button>

	<?php
	$disableFlag = "disabled";
	$disableImg = "_off";
	if (!$disEndPage){
		$disableFlag="";
		$disableImg="";
	}
	?>

	<button <?php echo $disableFlag?> class='button' title='最後' onclick="doEndPage()">
	<img src='cstore_lib/sugar_include/end<?php echo $disableImg?>.gif' alt='最後' align='absmiddle' border='0' width='13' height='11'>
	</button>
	</span>
	</td>
</table>
<center>
<TABLE CELLPADDING=2 BORDER=1 width=80%>
	<THEAD>
		<TR>
			<?php if ($choice_mode=='multi') { ?>
			<TH class=tableheader style="font-size:14px;text-align:center" >&nbsp;</TH>
			<?php } ?>
			<TH class=tableheader style="font-size:14px" >項次</TH>
			<TH class=tableheader style="font-size:14px" >採購單號</TH>
			<TH class=tableheader style="font-size:14px" >入系統日</TH>
			<TH class=tableheader style="font-size:14px" >採購日期</TH>
			<TH class=tableheader style="font-size:14px" >供應商</TH>
			<TH class=tableheader style="font-size:14px" >公司訂單編號</TH>
			<?php if ($choice_mode==null) { ?>
			<TH class=tableheader style="font-size:14px" >修改</TH>
			<TH class=tableheader style="font-size:14px" >刪除</TH>
			<?php } ?>
		</TR>
	</THEAD>
	
	<TBODY>
	<?php
		$itemIndex = $PageCtrl['start_row'];
		if ($search_result!=null) {
			while ($row=DB_fetch_array($search_result)) { 
				$itemIndex = $itemIndex + 1;
				$class = "class=OddTableRows";
				if ( ($itemIndex % 2)==0 ) {
					$class = "class=EvenTableRows";	
				}  
	?>
		<TR <?php echo $class ?> >
			<?php if ($choice_mode=='multi') { ?>
			
			<TD align=center>
			<input <?php echo getCheckedHtml($row['id'],'in',$selected_key) ?>  type=checkbox name=selItem onclick="doMultiSel(this)" value="<?php echo $row['id']."<CBK>".$row['orderno']?>">
			</TD>
			<?php } ?>
			<TD style="font-size:14px;text-align:center" width="2%" align=center ><?php echo $itemIndex?></TD>
			<?php if ($choice_mode=='multi') { ?>
			<?php } else if ($choice_mode=='single') { ?>
			<TD style="font-size:14px;text-align:center" ><a href="javascript:doSingleOK('<?php echo $row['id'].'<CBK>'.$row['orderno']?>')"><?php echo HtmlStr($row['orderno'])?></a></TD>
			<?php } else { ?>
			<TD style="font-size:14px;text-align:center" ><a href="javascript:doDetail('<?php echo $row['id'] ?>')"><?php echo HtmlStr($row['orderno'])?></a></TD> 
			<?php } ?>	
			<TD style="font-size:14px;text-align:center" ><?php echo HtmlStr($row['sys_date']) ?></TD>
			<TD style="font-size:14px;text-align:center" ><?php echo HtmlStr($row['order_date']) ?></TD>
			<TD style="font-size:14px" ><?php echo getColumnValue($db,'suppliers','suppname',"supplierid='{$row['supplierid']}'"); ?></TD>
			<TD style="font-size:14px" ><?php echo HtmlStr($row['inner_transno']) ?></TD>
			<?php if ($choice_mode==null) { ?>
			<TD style="font-size:14px;text-align:center"><a href="javascript:doEdit('<?php echo $row['id']?>')"><img border=0 src="cstore_lib/sugar_include/edit.gif"></a></TD>
			<TD style="font-size:14px;text-align:center"><a href="javascript:doDelete('<?php echo $row['id']?>')"><img border=0 src="cstore_lib/sugar_include/delete.gif"></a></TD>
			<?php } ?>
		</TR>
	<?php
			}
		}
	?>  
	</TBODY>
</TABLE>
</center>
<TABLE CELLPADDING=2 BORDER=1 width=100%>
	<span>
	<td class=OddTableRows colspan=7 style=text-align:right>

	<?php
	$disableFlag = "disabled";
	$disableImg = "_off";
	if (!$disStartPage){
		$disableFlag="";
		$disableImg=""; 
	}
	?>

	<button <?php echo $disableFlag?> title='起頭' class='button' onclick="doStartPage()">
	<img src='cstore_lib/sugar_include/start<?php echo $disableImg?>.gif' alt='起頭' align='absmiddle' border='0' width='13' height='11'>
	</button>
	<?php
	$disableFlag = "disabled";
	$disableImg = "_off";
	if (!$disLastPage){    
		$disableFlag="";
		$disableImg="";  
	}
	?>
	<button <?php echo $disableFlag?> class='button' title='上頁' onclick="doLastPage()">
	<img src='cstore_lib/sugar_include/previous<?php echo $disableImg?>.gif' alt='上頁' align='absmiddle' border='0' width='10' height='11'>
	</button>
	<?php
		$PageCtrl = $_SESSION['PurchExpensePageCtrl'];
	?>
	<span class='pageNumbers'>總筆數:<?php echo $PageCtrl['rows_total']?>&nbsp;<?php echo "(第".$PageCtrl['current_page']."頁 - 共".$PageCtrl['page_total']."頁)" ?></span>
	<?php
	$disableFlag = "disabled";
	$disableImg = "_off";
	if (!$disNextPage){    
		$disableFlag="";
		$disableImg="";
	}
	?>
	<button <?php echo $disableFlag?> class='button' title='下頁' onclick="doNextPage()">
	<img src='cstore_lib/sugar_include/next<?php echo $disableImg?>.gif' alt='下頁' align='absmiddle' border='0' width='10' height='11'>
	</button>
	<?php
	$disableFlag = "disabled";
	$disableImg = "_off";
	if (!$disEndPage){
		$disableFlag="";
		$disableImg="";
	}
	?>
	<button <?php echo $disableFlag?> class='button' title='最後' onclick="doEndPage()">
	<img src='cstore_lib/sugar_include/end<?php echo $disableImg?>.gif' alt='最後' align='absmiddle' border='0' width='13' height='11'>
	</button>

	</span>
	</td>

</table>
<?php if ($choice_mode=='multi') { ?>	
	<center>
	<INPUT class="button_action" TYPE=button name='btnSearch' VALUE='確定' onclick="doMultiOK();">&nbsp;
	<INPUT class="button_action" TYPE=button name='btnSearch' VALUE='離開' onclick="window.close();">&nbsp;&nbsp;
	</center>
<?php } ?>
<?php
} // end of SearchView
?>
<?php
if ($error_message!=null) {
?>
	<script language=javascript>
	alert(replaceAll('<?php echo $error_message?>','<br>','\r\n'));
	</script>
<?php
}
?>

<?php
function getUserByUserId($user_id) 
{               
    $query = DB::table('users')
                ->select('users.*')
                ->where('id', '=', $user_id);
    $resultData = $query->first();
	if (!empty($resultData)) {
	return $resultData;
	}
	else {
	 return false;	
	}
}

function getAffiliateBalanceByUserId($user_id) 
{               
    $query = DB::table('referral_commissions')
                ->select('referral_commissions.*')
                ->where('referrer_id', '=', $user_id);
    $resultData = $query->sum('referral_commissions.amount');
	if (!empty($resultData)) {
	return $resultData;
	}
	else {
	 return false;	
	}
}


function getUsedAffiliateBalanceByUserId($user_id) 
{               
    $query = DB::table('dba_transactions')
                ->select('dba_transactions.*')
                ->where('user_id', '=', $user_id)
                ->where('trans_for', '=','AFFILIATE SWAP');
    $resultData = $query->sum('dba_transactions.sender_real_value');
	if (!empty($resultData)) {
	return $resultData;
	}
	else {
	 return false;	
	}
}
function getgiftcarddetail($id)
{
 $query = DB::table('gift_cards')->select('gift_cards.*')->where('d_trans_id', '=', $id);
 $resultData = $query->first();
 if (!empty($resultData)) {
  return $resultData;
 }
 else {
  return false;	
 } 	
}

function getAgentById($agent_id)
{
 $query = DB::table('agents')->select('agents.*')->where('id', '=', $agent_id);
 $resultData = $query->first();
 if (!empty($resultData)) {
  return $resultData;
 }
 else {
  return false;	
 } 	
}

function getUserCardType($account_category)
{
 if ($account_category == "Silver")
  return "silver-vcard";
 else if ($account_category == "Gold")
  return "gold-vcard";
 else if ($account_category == "Platinum")
  return "platinum-vcard";
 else if ($account_category == "Private Wealth" || $account_category == "Enterprises")
  return "black-vcard"; 
}

function getUserByAccNum($accNum)
{
  $query = DB::table('users')->select('users.*')->where('account_number', '=', $accNum);
  $resultData = $query->first();
  if (!empty($resultData)) {
	return $resultData;
  }
  else {
	return false;	
  }	
}

function cc_decrypt($str)
{
  $ciphering = env('CIPHERING'); //"AES-128-CTR";
  $options = 0;
  $decryption_iv = env('ENCRYPT_IV'); //'1234567891011121';
  $decryption_key = env('ENCRYPT_KEY'); //"A87fAkIWLu8C1sLbzottqIMXn3g45apwogvFTF74";
  $decryption = openssl_decrypt ($str, $ciphering, $decryption_key, $options, $decryption_iv);
  return $decryption;
}

function getAccountDetail($trans_from)
{
  if ($trans_from == 'wireWithdraw') {
    return "PBC Wallet";
  }
  else {
   $data = explode("#",$trans_from);
   $transTyp = $data[0];
   $transAcc = $data[1];
   if (trim(strtolower($transTyp)) == 'credircard') {
	 $query = DB::table('cards')->select('cards.*')->where('cards.id',$transAcc);
     $resultData = $query->first();
	 return $resultData->card_name .' '.cc_decrypt($resultData->card_number);	
   }
   else if (trim(strtolower($transTyp)) == 'adminardjustbal') {
	 return "Adjust Balance";  
   }
   else {
   $query = DB::table('accounts')->select('accounts.*')->where('account_number','LIKE','%'.$transAcc.'');
   $resultData = $query->first();
   return $resultData->account_bank_name .' '.cc_decrypt($resultData->account_number);
   }
   
  }
}

function getTransactionFee($transId,$receiver_id)
{
 $query = DB::table('transactions')->select('transactions.amount')->where('transactions.refrence_id',$transId)->where("receiver_id",$receiver_id);
 $resultData = $query->first();	
 if (!empty($resultData))
 {
   return $resultData->amount; 
 }
 else {
   return 0;	 
 }
}

function searchFeeTrans($transId)
{
  $query = DB::table('transactions')->select('transactions.*')->where('transactions.refrence_id',$transId);
  $resultData = $query->get();
  if (Count($resultData) > 0) {
	return $resultData;  
  }
  else {
	return false;  
  }
}

function isMerchantRequest($user_id)
{
  $query = DB::table('request_for_merchants')->select('request_for_merchants.*')->where('request_for_merchants.user_id',$user_id);
  $resultData = $query->first();
  if (!empty($resultData))
  {
	if ($resultData->user_id == $user_id and $resultData->status == 2) {
	  return true; 
	}
	else {
      return false;		
	}
  }
}

function getTransFee($transID)
{
  $query = DB::table("transactions")->select("transactions.*")->where("refrence_id",$transID);
  $res = $query->first();
  if (!empty($res)) {
	return $res->amount ."###". $res->fees_description;
  }
  else {
	return '0';  
  }
}

function getPaymntTypAndRefId($transID)
{
$query = DB::table("transactions")->select("transactions.*")->where("id",$transID);
$res = $query->first();
if (!empty($res)) {
 $paymntTypeArr = explode("#",$res->trans_from);
 $paymntType = $paymntTypeArr[0];
 
 $sUser = DB::table('users')->select("users.first_name","users.last_name")->where("id",$res->user_id)->first();
 if (!empty($sUser)) {
  $senderNm = $sUser->first_name." ".$sUser->last_name;
 }
 else {
   $senderNm = 'NA'; 
 }
 
 $rUser = DB::table('users')->select("users.first_name","users.last_name")->where("id",$res->receiver_id)->first();
 if (!empty($rUser)) {
  $receiverNm = $rUser->first_name." ".$rUser->last_name;
 }
 else {
   $receiverNm = 'NA'; 
 }
 
 return $paymntType."###".$res->refrence_id."###".$senderNm."###".$receiverNm;
}
else {
 return false;	
}
}

function getUserType($user_id)
{
$query = DB::table("users")->select("users.user_type")->where("id",$user_id);
$res = $query->first();
if (!empty($res))
return $res->user_type;
else
return false;
}

function getPermissionByRoleId($role_id)
{
 $permissions = DB::table('permissions')->select("permissions.*")->where("role_id",$role_id)->get();
 if (Count($permissions) > 0)
 {
   $permissionStr = '';	 
   foreach($permissions as $val)
   {
	 $permissionStr.=$val->permission_name.",";   
   }
   $permissionStr = rtrim($permissionStr,",");
   return $permissionStr;
 }
 else {
  return false;
 } 
}

function validatePermission($role_id,$permission)
{
 $flag = DB::table('permissions')->select('permissions.id')->where('role_id',$role_id)->where('permission_name',$permission)->first();
 if (!empty($flag))
  return true;
 else
  return false;
}

function getRoleNameById($role_id)
{
 $role = DB::table('roles')->select('roles.role_name')->where('id',$role_id)->first();
 if (!empty($role))
 return $role->role_name;
 else
 return false; 
}

function getAdminNameById($id)
{
 $admin = DB::table('admins')->select('admins.*')->where('id',$id)->first();
 if (!empty($admin)) {
  return $admin->username;
 }
 else {
  return false; 
 }
}

function getTransactionrecord($id,$table)
{
 $flagmanual_deposits = DB::table($table)->select($table.'.*', 'users.user_type','users.first_name','users.last_name','users.business_name','users.director_name','users.slug')->join('users', 'users.id', '=', $table.'.user_id')->where($table.'.trans_id',$id)->first();

 if (!empty($flagmanual_deposits)){
  return $flagmanual_deposits;
}else{
	return $flagmanual_deposits=array();  
}
}

function isAgent($user_id)
{
  $flag = DB::table('agents')->select('agents.id')->where('user_id',$user_id)->first();
  if (!empty($flag))
	return true;
  else
	return false;  
}

function getAccountById($account_id)
{
  $account = DB::table('withdraw_accounts')->select('withdraw_accounts.*')->where('id',$account_id)->first();
  if (!empty($account))
	return $account;
  else
	return false;
}
if (!function_exists('getWalletData')) {
  function getWalletData()
  {
    return [
      'BJ' => [
        ['manager' => 'MTN BENIN', 'code' => 229, 'flag' => HTTP_PATH.'/public/assets/front/images/Benin.png'],
        ['manager' => 'MOOV BENIN', 'code' => 229, 'flag' => HTTP_PATH.'/public/assets/front/images/Benin.png'],
      ],
      'BF' => [
        ['manager' => 'ORANGE BURKINA', 'code' => 226, 'flag' => HTTP_PATH.'/public/assets/front/images/Burkina-Faso.png'],
      ],
      'GW' => [
        ['manager' => 'MTN GUINEA BISSAU', 'code' => 245, 'flag' => HTTP_PATH.'/public/assets/front/images/Guinea-Bissau.png'],
      ],
      'ML' => [
        ['manager' => 'ORANGE MALI', 'code' => 223, 'flag' => HTTP_PATH.'/public/assets/front/images/MALI.png'],
      ],
      'NE' => [
        ['manager' => 'AIRTEL NIGER', 'code' => 227, 'flag' => HTTP_PATH.'/public/assets/front/images/Niger.png'],
        ['manager' => 'MOOV NIGER', 'code' => 227, 'flag' => HTTP_PATH.'/public/assets/front/images/Niger.png'],
      ],
      'SN' => [
        ['manager' => 'FREE MONEY SENEGAL', 'code' => 221, 'flag' => HTTP_PATH.'/public/assets/front/images/SENEGAL.png'],
        ['manager' => 'ORANGE SENEGAL', 'code' => 221, 'flag' => HTTP_PATH.'/public/assets/front/images/SENEGAL.png'],
      ],
      'TG' => [
        ['manager' => 'MOOV AFRICA-TOGO', 'code' => 228, 'flag' => HTTP_PATH.'/public/assets/front/images/TOGO.png'],
        ['manager' => 'TOGOCOM - TMONEY', 'code' => 228, 'flag' => HTTP_PATH.'/public/assets/front/images/TOGO.png'],
      ],
      'CM' => [
        ['manager' => '', 'code' => 237, 'flag' => HTTP_PATH.'/public/assets/front/images/cameroon.png']
      ],
      'CI' => [
        ['manager' => 'MTN COTE D\'IVOIRE', 'code' => 225, 'flag' => HTTP_PATH.'/public/assets/front/images/coast.png'],
        ['manager' => 'MOOV COTE D\'IVOIRE', 'code' => 225, 'flag' => HTTP_PATH.'/public/assets/front/images/coast.png'],
        ['manager' => 'ORANGE COTE D\'IVOIRE', 'code' => 225, 'flag' => HTTP_PATH.'/public/assets/front/images/coast.png'],
      ],
      'GA' => [
        ['manager' => '', 'code' => 241, 'flag' => HTTP_PATH.'/public/assets/front/images/Gabon.png']
      ],
      'FR' => [
        ['manager' => '', 'code' => 33, 'flag' => HTTP_PATH.'/public/assets/front/images/france.png']
      ],
    ];
  }
}
?>

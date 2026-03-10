{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
<script type="text/javascript">
$(document).ready(function ($) {
    $('.close_image').hide();
    $('a[rel*=facebox]').facebox({
        closeImage: '{!! HTTP_PATH !!}/public/img/close.png'
    });

    $('.dropdown-menu a').on('click', function (event) {
        $(this).parent().parent().parent().toggleClass('open');
    });


});

function showPop(id) {
    $('#transModal' + id).modal('show');
}


</script>
@if(!$allrecords->isEmpty()) 
<?php
$timeSecMonth = '';
$timeSecYear = '';
$oldTimeSecMonth = '';
$oldTimeSecYear = '';
?>
@forelse($allrecords as $allrecord)
<?php
global $tranType;
$spayment_mode = $allrecord->payment_mode;
$transArr = array();
$user_id = Session::get('user_id');
if ($allrecord->receiver_id == 0) {
    $transArr['trans_from'] = $allrecord->payment_mode;
    $transArr['sender'] = $allrecord->User->name;
    $transArr['sender_id'] = $allrecord->user_id;
    $transArr['sender_phone'] = $allrecord->User->phone;
    $transArr['receiver'] = 'Admin';
    $transArr['receiver_id'] = $allrecord->receiver_id;
    $transArr['receiver_phone'] = 0;
    $transArr['trans_type'] = $tranType[$allrecord->trans_type]; //1=Credit;2=Debit;3=topup
} elseif ($allrecord->user_id == $user_id) { //User is sender
    $transArr['trans_from'] = $allrecord->payment_mode;
    $transArr['sender'] = $allrecord->User->name;
    $transArr['sender_id'] = $allrecord->user_id;
    $transArr['sender_phone'] = $allrecord->User->phone;
    $transArr['receiver'] = $allrecord->Receiver->name;
    $transArr['receiver_id'] = $allrecord->receiver_id;
    $transArr['receiver_phone'] = $allrecord->Receiver->phone;
    $transArr['trans_type'] = $tranType[$allrecord->trans_type]; //1=Credit;2=Debit;3=topup

    if ($allrecord->payment_mode == 'Send Money' || $allrecord->payment_mode == 'Shop Payment' || $allrecord->payment_mode == 'Online Shopping'  || $allrecord->payment_mode == 'Merchant Withdraw') {
        $allrecord->payment_mode = 'wallet2wallet'; //1=Credit;2=Debit;3=topup
        $transArr['payment_mode'] = $allrecord->payment_mode;
        $transArr['trans_from'] = $allrecord->payment_mode;
    }

    if ($allrecord->payment_mode != 'Cash card') {
        if ($allrecord->trans_type == 2) {
            $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
        } else {
            $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
        }
    }

    if ($allrecord->payment_mode == 'Agent Deposit') {
        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
        $transArr['receiver'] = $allrecord->User->name;
        $transArr['receiver_id'] = $allrecord->user_id;
        $transArr['receiver_phone'] = $allrecord->User->phone;
        $transArr['sender'] = $allrecord->Receiver->name;
        $transArr['sender_id'] = $allrecord->receiver_id;
        $transArr['sender_phone'] = $allrecord->Receiver->phone;
    }

    if ($allrecord->payment_mode == 'Refund' && $allrecord->trans_type == 1) {
        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
        $transArr['receiver'] = $allrecord->User->name;
        $transArr['receiver_id'] = $allrecord->user_id;
        $transArr['receiver_phone'] = $allrecord->User->phone;
        $transArr['sender'] = $allrecord->Receiver->name;
        $transArr['sender_id'] = $allrecord->receiver_id;
        $transArr['sender_phone'] = $allrecord->Receiver->phone;
    }
    if ($allrecord->payment_mode == 'wallet2wallet' && $allrecord->trans_type == 2) {
        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
    }

    if ($allrecord->payment_mode == 'Withdraw') {
        $transArr['receiver'] = $allrecord->User->name;
        $transArr['receiver_id'] = $allrecord->user_id;
        $transArr['receiver_phone'] = $allrecord->User->phone;
        $transArr['sender'] = $allrecord->Receiver->name;
        $transArr['sender_id'] = $allrecord->receiver_id;
        $transArr['sender_phone'] = $allrecord->Receiver->phone;
        $transArr['trans_type'] = $tranType[$allrecord->trans_type]; //1=Credit;2=Debit;3=topup
    }
} else if ($allrecord->receiver_id == $user_id) { //USer is Receiver
    $transArr['trans_from'] = $allrecord->payment_mode;
    $transArr['sender'] = $allrecord->User->name;
    $transArr['sender_id'] = $allrecord->user_id;
    $transArr['sender_phone'] = $allrecord->User->phone;
    $transArr['receiver'] = $allrecord->Receiver->name;
    $transArr['receiver_id'] = $allrecord->receiver_id;
    $transArr['receiver_phone'] = $allrecord->Receiver->phone;
    $transArr['trans_type'] = $tranType[$allrecord->trans_type]; //1=Credit;2=Debit;3=topup

    if ($allrecord->trans_type == 2) {
        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
    }

    if ($allrecord->payment_mode == 'Send Money' || $allrecord->payment_mode == 'Shop Payment' || $allrecord->payment_mode == 'Online Shopping') {
        $allrecord->payment_mode = 'wallet2wallet'; //1=Credit;2=Debit;3=topup
    }

    if ($allrecord->payment_mode == 'Withdraw' && $allrecord->trans_type == 2) {
        $transArr['receiver'] = $allrecord->User->name;
        $transArr['receiver_id'] = $allrecord->user_id;
        $transArr['receiver_phone'] = $allrecord->User->phone;
        $transArr['sender'] = $allrecord->Receiver->name;
        $transArr['sender_id'] = $allrecord->receiver_id;
        $transArr['sender_phone'] = $allrecord->Receiver->phone;
        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
    }



    if ($allrecord->payment_mode == 'Refund' && $allrecord->trans_type == 1) {
        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
    }
    if ($userInfo->user_type != 'Merchant') {
        if ($allrecord->payment_mode == 'Refund' && $transArr['trans_type'] == 'Debit') {
            $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
        }
    } else {
        if ($allrecord->payment_mode == 'Refund' && $allrecord->trans_type == 1) {
            $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
//                                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
//                                    $transArr['receiver_id'] = $val->user_id;
//                                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
//                                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
//                                    $transArr['sender_id'] = $val->receiver_id;
//                                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
        }
        if ($allrecord->payment_mode == 'Refund' && $allrecord->trans_type == 1 && $allrecord->refund_status == 0) {
            $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
        }
    }

    if ($allrecord->payment_mode == 'Agent Deposit') {
        if ($allrecord->trans_type != 4) {
            $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
        } else {
            $transArr['trans_type'] = $tranType[4]; //1=Credit;2=Debit;3=topup
        }

        $transArr['receiver'] = $allrecord->User->name;
        $transArr['receiver_id'] = $allrecord->user_id;
        $transArr['receiver_phone'] = $allrecord->User->phone;
        $transArr['sender'] = $allrecord->Receiver->name;
        $transArr['sender_id'] = $allrecord->receiver_id;
        $transArr['sender_phone'] = $allrecord->Receiver->phone;
    }
}

if($spayment_mode=="Merchant Withdraw" || $spayment_mode=="Online Shopping"){



    if ($userInfo->user_type != 'Individual') { //echo $transArr['trans_type'];
        if ($transArr['trans_type'] == 'Credit') {
            if ($allrecord->payment_mode == "Withdraw") {
                $description = __('message.' . $allrecord->payment_mode . '');
            } else {
                $description = $userInfo->user_type == 'Merchant' ? $transArr['sender_phone'] : $transArr['receiver_phone'];
            }
    
            if ($userInfo->user_type == 'Merchant') {
                if ($allrecord->payment_mode == 'Refund') {
                    $tranTitle = __('message.Refund from') . " " . $transArr['sender'];
                    $amount = '+ IQD ' . number_format($allrecord->total_amount, 2);
                    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                } else {
                    $tranTitle = __('message.Received from') . " " . $transArr['sender'];
    
    
    
    
                    $remain=$allrecord->total_amount-$allrecord->transaction_amount;
                    $plus=$allrecord->total_amount;
        
                    if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                        $amount = '+ IQD ' . number_format($remain, 2);
                    }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                        $amount = '+ IQD ' . number_format($plus, 2);
                       }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                        $amount = '+ IQD ' . number_format($remain, 2);
                    }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                        $amount = '+ IQD ' . number_format($plus, 2);
                       }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
                        if($spayment_mode=="Merchant Withdraw"){
                       
                            if($allrecord->receiver_id==$userInfo->id){
        
                                $amount = '+ IQD ' . number_format($plus, 2);
                            }else{
                
                                $amount = '+ IQD ' . number_format($plus, 2);
                            }
                        }else{
        
                            $amount = '+ IQD ' . number_format($remain, 2);
        
        
                        }
                       }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
                        if($spayment_mode=="Merchant Withdraw"){
                            $amount = '+ IQD ' . number_format($remain, 2);
        
                            }else{
        
                                $amount = '+ IQD ' . number_format($plus, 2);
        
                            }
                       }else{
         
                        $amount = '+ IQD ' . number_format($allrecord->total_amount, 2);
                    }
    
    
                
    
                    
                     $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                }
            } else {
                $tranTitle = __('message.Received from') . " " . $transArr['sender'];
                
    
                $remain=$allrecord->total_amount-$allrecord->transaction_amount;
                $plus=$allrecord->total_amount;
    
                if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                    $amount = '+ IQD ' . number_format($remain, 2);
                }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                    $amount = '+ IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                    $amount = '+ IQD ' . number_format($remain, 2);
                }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                    $amount = '+ IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
                    $amount = '+ IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
                    if($spayment_mode=="Merchant Withdraw"){
                        $amount = '+ IQD ' . number_format($remain, 2);
    
                        }else{
    
                            $amount = '+ IQD ' . number_format($plus, 2);
    
                        }
                   }else{
     
                    $amount = '+ IQD ' . number_format($allrecord->total_amount, 2);
                }
    
               
                 $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
            }
        } elseif ($transArr['trans_type'] == 'Topup') {
            $tranTitle = __('message.Money Added To Wallet');
            $description = $allrecord->payment_mode;
            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
        } elseif ($transArr['trans_type'] == 'Request') {
            if ($allrecord->payment_mode == 'Withdraw') {
                $tranTitle = __('message.Money Requested From') . " " . $transArr['sender'];
                $description = $allrecord->payment_mode;
            } else {
                $tranTitle = __('message.Deposit to') . " " . $transArr['receiver'];
                $description = $transArr['receiver_phone'];
            }
    
    
            if ($userInfo->user_type == 'Merchant') {
                if ($allrecord->payment_mode == 'Withdraw') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                } else {
                    $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                }
            } else {
                if ($allrecord->payment_mode == 'Withdraw') {
                    $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                } else {
                    $amount = '- IQD ' . number_format($allrecord->amount, 2);
                }
            }
        } else {
            if ($allrecord->payment_mode == 'wallet2wallet') {
                $tranTitle = __('message.Paid to') . " " . $transArr['receiver'];
                $description = $transArr['receiver_phone'];
                $remain=$allrecord->total_amount;
                $plus=$allrecord->total_amount+$allrecord->transaction_amount;
    
                if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
                    if($spayment_mode=="Merchant Withdraw"){
                        $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
           
                           }else{
           
                               $amount = '- IQD ' . number_format($plus, 2);
           
                           }
                }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
    
                    if($spayment_mode=="Merchant Withdraw"){
                    $amount = '- IQD ' . number_format($plus, 2);
    
                    }else{
    
                        $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
    
                    }
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                    $amount = '- IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                    $amount = '- IQD ' . number_format($plus, 2);
                   }else{
     
                 $amount = '- IQD ' . number_format($remain, 2);
                }
                
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
            } else {
                if ($allrecord->payment_mode == "Withdraw") {
                    if ($userInfo->user_type == 'Merchant') {
                        $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                        $description = $transArr['sender_phone'];
                        $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                        $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                    } else {
                        $tranTitle = __('message.Withdraw by') . " " . $transArr['receiver'];
                        $description = $transArr['receiver_phone'];
                        $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                        $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                    }
                } else {
                    if ($allrecord->payment_mode == "Refund") {
                        $tranTitle = __('message.Refund to') . " " . $transArr['sender'];
                        $description = $transArr['sender_phone'];
                        $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                        $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                    } else {
                        if ($userInfo->user_type == 'Merchant') {
                            if ($allrecord->payment_mode == "Agent Deposit") {
                                $tranTitle = __('message.Deposited to') . " " . $transArr['receiver'];
                            } elseif ($allrecord->payment_mode == "Withdraw") {
                                $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                            } else {
                                $tranTitle = $allrecord->payment_mode;
                            }
    
                            if ($transArr['receiver_phone'] == "0") {
                                $description = $transArr['sender_phone'];
                            }
                            if ($allrecord->payment_mode != "Withdraw") {
                                $description = $transArr['sender_phone'];
                            } else {
                                $description = $transArr['receiver_phone'];
                            }
    
                            $amount = '- IQD ' . number_format($allrecord->amount, 2);
                            // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                        } else {
                            if ($allrecord->status != 'Success') {
                                $depositText = __('message.Deposited to');
                            } else {
                                $depositText = __('message.Deposited to');
                            }
    
                            if ($allrecord->payment_mode == "Agent Deposit") {
                                $tranTitle = $depositText . ' ' . $transArr['receiver'];
                            } elseif ($allrecord->payment_mode == "Withdraw") {
                                $tranTitle = __('message.Withdraw by') . " " . $transArr['receiver'];
                            } else {
                                $tranTitle = __('message.' . $allrecord->payment_mode . '');
                            }
    
                            if ($transArr['receiver_phone'] == "0") {
                                $description = $transArr['sender_phone'];
                            } else {
                                $description = $transArr['receiver_phone'];
                            }
    
                            if ($allrecord->payment_mode == "Agent Deposit") {
                                $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                                // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                            } elseif ($allrecord->payment_mode == "Withdraw") {
                                $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                            } else {
                                $amount = '- IQD ' . number_format($allrecord->amount, 2);
                                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                            }
                        }
                    }
                }
            }
        }
    } else {
        if ($transArr['trans_type'] == 'Credit') {
            $tranTitle = $allrecord->payment_mode == "Refund" ? __('message.Refund from') . " " . $transArr['sender'] : __('message.Received from') . " " . $transArr['sender'];
            $description = $allrecord->payment_mode == "Agent Deposit" ? $allrecord->payment_mode : $transArr['sender_phone'];
            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
    
            $remain=$allrecord->total_amount-$allrecord->transaction_amount;
            $plus=$allrecord->total_amount;
    
            if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                $amount = '+ IQD ' . number_format($remain, 2);
            }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                $amount = '+ IQD ' . number_format($plus, 2);
               }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                $amount = '+ IQD ' . number_format($remain, 2);
            }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                $amount = '+ IQD ' . number_format($plus, 2);
               }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
                $amount = '+ IQD ' . number_format($remain, 2);
               }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
                if($spayment_mode=="Merchant Withdraw"){
                    $amount = '+ IQD ' . number_format($remain, 2);
    
                    }else{
    
                        $amount = '+ IQD ' . number_format($plus, 2);
    
                    }
               }
        } elseif ($transArr['trans_type'] == 'Topup') {
            $tranTitle = __('message.Money Added To Wallet');
            $description = __('message.' . $allrecord->payment_mode . '');
            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
            // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
        } elseif ($transArr['trans_type'] == 'Request') {
            $tranTitle = __('message.Money Requested From') . " " . $transArr['sender'];
            $description = $allrecord->payment_mode;
            $amount = $allrecord->payment_mode == "Withdraw" ? "- IQD " . number_format($allrecord->total_amount, 2) : "+ IQD " . number_format($allrecord->amount, 2);
            $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
        } else {
            if ($allrecord->payment_mode == 'wallet2wallet') {
                $tranTitle = __('message.Paid to') . " " . $transArr['receiver'];
                $description = $transArr['receiver_phone'];
                $remain=$allrecord->total_amount-$allrecord->transaction_amount;
                $plus=$allrecord->total_amount+$allrecord->transaction_amount;
    
                if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
                    if($spayment_mode=="Merchant Withdraw"){
                        $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
           
                           }else{
           
                               $amount = '- IQD ' . number_format($plus, 2);
           
                           }
                }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
                    if($spayment_mode=="Merchant Withdraw"){
                        $amount = '- IQD ' . number_format($plus, 2);
        
                        }else{
        
                            $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
        
                        }
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                    $amount = '- IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                    $amount = '- IQD ' . number_format($plus, 2);
                   }else{
     
                 $amount = '- IQD ' . number_format($remain, 2);
                }
                
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
            } else {
                if ($allrecord->payment_mode == "Withdraw") {
                    $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                    $description = $transArr['sender_phone'];
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                } else {
                    $tranTitle = __('message.' . $allrecord->payment_mode . '');
                    $description = $transArr['sender_phone'];
                    $amount = '- IQD ' . number_format($allrecord->amount, 2);
                    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                }
            }
        }
    }
    
    if ($allrecord->payment_mode == 'Credited by admin' || $allrecord->payment_mode == 'Debited by admin') {
        $tranTitle = __('message.' . $allrecord->payment_mode . '');
        $description = 'Admin';
    }
    
    
    if ($description == 'Withdraw') {
        $description = __('message.Withdraw');
    }
    
    if ($description == 'Agent Deposit') {
        $description = __('message.Agent Deposit');
    }
    
    $trans_amount = '';
    
    if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
        if($spayment_mode=="Merchant Withdraw"){


            if ($allrecord->user_id == $userInfo->id && $allrecord->fee_pay_by == 'Merchant' ) {
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);

            }

       // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);

        }else{
            if ($allrecord->receiver_id == $userInfo->id && $allrecord->fee_pay_by == 'Merchant' ) {
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);

            }

            if ($allrecord->user_id == $userInfo->id && $allrecord->fee_pay_by == 'User' ) {

               
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);

            }

        }
    }
    
    
    
    
    
    
    if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
        $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
    
    
       
    }
    
    
    if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
        $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
    
    
       
    }
    
    
    if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {

        if($spayment_mode=="Merchant Withdraw"){
     

            if ($allrecord->receiver_id == $userInfo->id && $allrecord->fee_pay_by == 'User' ) {
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);

            }

        }else{
           // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);

           if ($allrecord->user_id == $userInfo->id && $allrecord->fee_pay_by == 'User' ) {

               
            $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);

        }

        }
    }
    






}else{


if ($userInfo->user_type != 'Individual') { //echo $transArr['trans_type'];
    if ($transArr['trans_type'] == 'Credit') {
        if ($allrecord->payment_mode == "Withdraw") {
            $description = __('message.' . $allrecord->payment_mode . '');
        } else {
            $description = $userInfo->user_type == 'Merchant' ? $transArr['sender_phone'] : $transArr['receiver_phone'];
        }

        if ($userInfo->user_type == 'Merchant') {
            if ($allrecord->payment_mode == 'Refund') {
                $tranTitle = __('message.Refund from') . " " . $transArr['sender'];
                $amount = '+ IQD ' . number_format($allrecord->total_amount, 2);
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
            } else {
                $tranTitle = __('message.Received from') . " " . $transArr['sender'];
                $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
            }
        } else {
            $tranTitle = __('message.Received from') . " " . $transArr['receiver'];
            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
            // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
        }
    } elseif ($transArr['trans_type'] == 'Topup') {
        $tranTitle = __('message.Money Added To Wallet');
        $description = $allrecord->payment_mode;
        $amount = '+ IQD ' . number_format($allrecord->amount, 2);
    } elseif ($transArr['trans_type'] == 'Request') {
        if ($allrecord->payment_mode == 'Withdraw') {
            $tranTitle = __('message.Money Requested From') . " " . $transArr['sender'];
            $description = $allrecord->payment_mode;
        } else {
            $tranTitle = __('message.Deposit to') . " " . $transArr['receiver'];
            $description = $transArr['receiver_phone'];
        }


        if ($userInfo->user_type == 'Merchant') {
            if ($allrecord->payment_mode == 'Withdraw') {
                $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
            } else {
                $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
            }
        } else {
            if ($allrecord->payment_mode == 'Withdraw') {
                $amount = '+ IQD ' . number_format($allrecord->amount, 2);
            } else {
                $amount = '- IQD ' . number_format($allrecord->amount, 2);
            }
        }
    } else {
        if ($allrecord->payment_mode == 'wallet2wallet') {
            $tranTitle = __('message.Paid to') . " " . $transArr['receiver'];
            $description = $transArr['receiver_phone'];
            $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
            $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
        } else {
            if ($allrecord->payment_mode == "Withdraw") {
                if ($userInfo->user_type == 'Merchant') {
                    $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                    $description = $transArr['sender_phone'];
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                } else {
                    $tranTitle = __('message.Withdraw by') . " " . $transArr['receiver'];
                    $description = $transArr['receiver_phone'];
                    $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                }
            } else {
                if ($allrecord->payment_mode == "Refund") {
                    $tranTitle = __('message.Refund to') . " " . $transArr['sender'];
                    $description = $transArr['sender_phone'];
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                } else {
                    if ($userInfo->user_type == 'Merchant') {
                        if ($allrecord->payment_mode == "Agent Deposit") {
                            $tranTitle = __('message.Deposited to') . " " . $transArr['receiver'];
                        } elseif ($allrecord->payment_mode == "Withdraw") {
                            $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                        } else {
                            $tranTitle = $allrecord->payment_mode;
                        }

                        if ($transArr['receiver_phone'] == "0") {
                            $description = $transArr['sender_phone'];
                        }
                        if ($allrecord->payment_mode != "Withdraw") {
                            $description = $transArr['sender_phone'];
                        } else {
                            $description = $transArr['receiver_phone'];
                        }

                        $amount = '- IQD ' . number_format($allrecord->amount, 2);
                        // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                    } else {
                        if ($allrecord->status != 'Success') {
                            $depositText = __('message.Deposited to');
                        } else {
                            $depositText = __('message.Deposited to');
                        }

                        if ($allrecord->payment_mode == "Agent Deposit") {
                            $tranTitle = $depositText . ' ' . $transArr['receiver'];
                        } elseif ($allrecord->payment_mode == "Withdraw") {
                            $tranTitle = __('message.Withdraw by') . " " . $transArr['receiver'];
                        } else {
                            $tranTitle = __('message.' . $allrecord->payment_mode . '');
                        }

                        if ($transArr['receiver_phone'] == "0") {
                            $description = $transArr['sender_phone'];
                        } else {
                            $description = $transArr['receiver_phone'];
                        }

                        if ($allrecord->payment_mode == "Agent Deposit") {
                            $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                            // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                        } elseif ($allrecord->payment_mode == "Withdraw") {
                            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                            $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                        } else {
                            $amount = '- IQD ' . number_format($allrecord->amount, 2);
                            $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
                        }
                    }
                }
            }
        }
    }
} else {
    if ($transArr['trans_type'] == 'Credit') {
        $tranTitle = $allrecord->payment_mode == "Refund" ? __('message.Refund from') . " " . $transArr['sender'] : __('message.Received from') . " " . $transArr['sender'];
        $description = $allrecord->payment_mode == "Agent Deposit" ? $allrecord->payment_mode : $transArr['sender_phone'];
        $amount = '+ IQD ' . number_format($allrecord->amount, 2);
    } elseif ($transArr['trans_type'] == 'Topup') {
        $tranTitle = __('message.Money Added To Wallet');
        $description = __('message.' . $allrecord->payment_mode . '');
        $amount = '+ IQD ' . number_format($allrecord->amount, 2);
        // $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
    } elseif ($transArr['trans_type'] == 'Request') {
        $tranTitle = __('message.Money Requested From') . " " . $transArr['sender'];
        $description = $allrecord->payment_mode;
        $amount = $allrecord->payment_mode == "Withdraw" ? "- IQD " . number_format($allrecord->total_amount, 2) : "+ IQD " . number_format($allrecord->amount, 2);
        $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
    } else {
        if ($allrecord->payment_mode == 'wallet2wallet') {
            $tranTitle = __('message.Paid to') . " " . $transArr['receiver'];
            $description = $transArr['receiver_phone'];
            $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
            $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
        } else {
            if ($allrecord->payment_mode == "Withdraw") {
                $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                $description = $transArr['sender_phone'];
                $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
            } else {
                $tranTitle = __('message.' . $allrecord->payment_mode . '');
                $description = $transArr['sender_phone'];
                $amount = '- IQD ' . number_format($allrecord->amount, 2);
                $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
            }
        }
    }
}

if ($allrecord->payment_mode == 'Credited by admin' || $allrecord->payment_mode == 'Debited by admin') {
    $tranTitle = __('message.' . $allrecord->payment_mode . '');
    $description = 'Admin';
}


if ($description == 'Withdraw') {
    $description = __('message.Withdraw');
}

if ($description == 'Agent Deposit') {
    $description = __('message.Agent Deposit');
}

$trans_amount = '';

if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
}

if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
}



if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
    $trans_amount = 'IQD ' . number_format($allrecord->transaction_amount, 2);
}

}

?>
<div class="container mb-40 list_lng">
    <?php
    $timeSecMonth = date('m', strtotime($allrecord->created_at));
    $timeSecYear = date('Y', strtotime($allrecord->created_at));
//    if ($timeSecYear != $oldTimeSecYear) {
//        if ($timeSecMonth != $oldTimeSecMonth) {
//            echo '<h5 class="col-sm-12 p-0 his-head">' . date('F Y', strtotime($allrecord->created_at)) . '</h5>';
//        }
//    }

    if ($timeSecMonth != $oldTimeSecMonth) {
        if ($timeSecYear != $oldTimeSecYear) {
            $years = $timeSecYear;
        }
//        if (Session::get('locale')) {
//            if(Session::get('locale') == 'en'){
//                setlocale(LC_TIME, 'English');
//            } elseif(Session::get('locale') == 'ar'){
//                setlocale(LC_ALL, 'ar_AE.utf8');
//            } else{
//                setlocale(LC_TIME, 'Kurdish');
//            }
//            
//        } else{
//            setlocale(LC_TIME, 'Kurdish');
//        }
//        setlocale(LC_ALL, 'ar_AE.utf8');
//        echo Config::get('app.locale');
        echo '<h5 class="col-sm-12 p-0 his-head">' . strftime("%B %Y", strtotime($allrecord->created_at)) . '</h5>';
    }
    ?>
    <!--<h5 class="col-sm-12 p-0 his-head">November</h5>-->
    <!--<a class="" href="#info{!! $allrecord->id !!}" rel='facebox'>-->
    <a href="javascript:void(0);"  onclick="showPop('{{$allrecord->id}}')">
        <div class="history-box-main">
            <div class="col-sm-4 history-name-box history-sett">
                <?php
                global $tranStatus;
                $trans_status = $tranStatus[$allrecord->status];
                $class = '';
                if ($trans_status == 'Reject') {
                    $amount = str_replace('+ ', ' ', $amount);
                }
                ?>
                @if($trans_status == 'Success')
                @php $class = 'green'; @endphp
                <div class="name-tag success-name-tag">
                    <i class="fa fa-check"></i>
                </div>
                @elseif($trans_status == 'Reject')
                @php $class = 'red'; @endphp
                <div class="name-tag failed-name-tag">
                    <i class="fa fa-close"></i>
                </div>            
                @else
                @php $class = 'red'; @endphp
                <div class="name-tag pending-name-tag">
                    <i class="fa fa-exclamation"></i>
                </div>
                @endif
                <div class="history-name">
                    <h6>{{$tranTitle}}</h6>
                    <span>{{$description}}</span>
                </div>
            </div>
            <div class="col-sm-4 text-right history-detail">
                <span class="{{$class}}">
                    {{$amount}}
                </span>
                <small>
                    <?php
                    $trnsDt = date_create($allrecord->created_at);
                    $transDate = strftime("%d %B %Y, %I:%M %p", strtotime($allrecord->created_at));
//                    $transDate = date_format($trnsDt, "d M Y, h:i A");
                    ?>
                    {{$transDate}}
                </small>
            </div>
        </div>
    </a>
</div>

<div id="transModal{!! $allrecord->id !!}" class="modal fade" role="dialog" style="display: none;">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{__('message.Transaction Details')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="drt">
                    <div class="user_pop">
                        <div class="pop-heading">
                            @if($trans_status == 'Success')
                            <span>{{__('message.Transaction Successful')}}</span>
                            @elseif($trans_status == 'Reject')
                            <span>{{__('message.Transaction Failed')}}</span>
                            @else
                            <span>{{__('message.Transaction Pending')}}</span>
                            @endif
                            <span>{{$transDate}}</span>
                        </div>
                        <div class="pop-trans-detail">
                            @if($trans_status == 'Success')
                            @php $class = 'green'; @endphp
                            <div class="name-tag success-name-tag">
                                <i class="fa fa-check"></i>
                            </div>
                            @elseif($trans_status == 'Reject')
                            @php $class = 'red'; @endphp
                            <div class="name-tag failed-name-tag">
                                <i class="fa fa-close"></i>
                            </div>            
                            @else
                            @php $class = 'red'; @endphp
                            <div class="name-tag pending-name-tag">
                                <i class="fa fa-exclamation"></i>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="user_pop user_pop1">
                        <div class="trans-id">
                            <span>{{__('message.Transaction ID')}}</span>
                            <span>{{$allrecord->refrence_id}}</span>
                        </div>
                    </div>

                    @if($allrecord->payment_mode == 'Refund')

                    <?php
                    $transArr = explode('-', $allrecord->billing_description);
                    if (isset($transArr[1])) {
                        $addrecord1 = DB::table('transactions')
                                ->where('id', $transArr[1])
                                ->first();
                        ?>
                        <div class="user_pop user_pop1">
                            <div class="trans-id">
                                <span>{{__('message.Reference ID')}}</span>
                                <span>{{$addrecord1->refrence_id}}</span>
                            </div>
                        </div>
                    <?php }
                    ?>

                    @endif
                    <div class="user_pop user_pop1">
                        <div class="seprate_cls">
                            <div>{{__('message.Transaction Type')}}: {{$tranTitle}}</div>
                            <div>{{__('message.Date')}}: {{$transDate}}</div>
                            <div>{{__('message.Description')}}: {{$description}}</div>
                            <div>{{__('message.Amount')}}: {{$amount}}</div>
                            <?php if (isset($trans_amount) && !empty($trans_amount)) { ?>
                                <div>{{__('message.Transaction Fee')}}: {{$trans_amount}}</div>
                            <?php } ?>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--  <div class="modal-content">
        
     </div> -->
</div>
<!--<div class='facebox_cls' id="info{!! $allrecord->id !!}" style="display: none;">
    <div class="nzwh-wrapper ">
        <fieldset class="nzwh">
            <legend class="head_pop">{{__('message.Transaction Details')}}</legend>
            <div class="drt">
                <div class="user_pop">
                    <div class="pop-heading">
                        @if($trans_status == 'Success')
                        <span>{{__('message.Transaction Successful')}}</span>
                        @elseif($trans_status == 'Reject')
                        <span>{{__('message.Transaction Failed')}}</span>
                        @else
                        <span>{{__('message.Transaction Pending')}}</span>
                        @endif
                        <span>{{$transDate}}</span>
                    </div>
                    <div class="pop-trans-detail">
                        @if($trans_status == 'Success')
                        @php $class = 'green'; @endphp
                        <div class="name-tag success-name-tag">
                            <i class="fa fa-check"></i>
                        </div>
                        @elseif($trans_status == 'Reject')
                        @php $class = 'red'; @endphp
                        <div class="name-tag failed-name-tag">
                            <i class="fa fa-close"></i>
                        </div>            
                        @else
                        @php $class = 'red'; @endphp
                        <div class="name-tag pending-name-tag">
                            <i class="fa fa-exclamation"></i>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="user_pop user_pop1">
                    <div class="trans-id">
                        <span>{{__('message.Transaction ID')}}</span>
                        <span>{{$allrecord->refrence_id}}</span>
                    </div>
                </div>

                @if($allrecord->payment_mode == 'Refund')

<?php
$transArr = explode('-', $allrecord->billing_description);
if (isset($transArr[1])) {
    $addrecord1 = DB::table('transactions')
            ->where('id', $transArr[1])
            ->first();
    ?>
                        <div class="user_pop user_pop1">
                            <div class="trans-id">
                                <span>{{__('message.Reference ID')}}</span>
                                <span>{{$addrecord1->refrence_id}}</span>
                            </div>
                        </div>
<?php }
?>

                @endif
                <div class="user_pop user_pop1">
                    <div class="seprate_cls">
                        <div>{{__('message.Transaction Type')}}: {{$tranTitle}}</div>
                        <div>{{__('message.Date')}}: {{$transDate}}</div>
                        <div>{{__('message.Description')}}: {{$description}}</div>
                        <div>{{__('message.Amount')}}: {{$amount}}</div>
<?php if (isset($trans_amount) && !empty($trans_amount)) { ?>
                                <div>{{__('message.Transaction Fee')}}: {{$trans_amount}}</div>
<?php } ?>

                    </div>

                </div>
            </div>
        </fieldset>
    </div>
</div>-->
<?php
$oldTimeSecMonth = $timeSecMonth;
$oldTimeSecYear = $timeSecYear;
?>
@empty
<div class="container mb-40"><div class="col-sm-12"><div class="no_record">{{__('message.No records found.')}}</div></div></div>
@endforelse
@if(!$allrecords->isEmpty() && $allrecords->lastPage() > 1)
<div class="row">
    <div class="container">
        <div class="col-sm-12 head-left">
            <div class="shpagel">{{__('message.show_records',['pageRecord'=>$allrecords->perPage(),'totalRecord'=>$allrecords->total()])}} </div>
            <div class="topn_rightd ajaxpagee ddpagingshorting" id="pagingLinks" align="right" style="margin-left: auto !important;">
                <div class="panel-heading" style="align-items:center;">
                    {{$allrecords->appends(Input::except('_token'))->render()}}
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@else
<div class="container mb-40"><div class="col-sm-12"><div class="no_record">{{__('message.No records found.')}}</div></div></div>
@endif

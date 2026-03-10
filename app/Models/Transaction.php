<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Transaction extends Model
{
    use Sortable;
    
    public $sortable = ['id', 'name', 'trans_type', 'add_by', 'currency', 'real_value','amount', 'transaction_amount', 'company_name','total_amount','fee_pay_by', 'payment_mode', 'status', 'refrence_id','trans_to', 'created_at', 'updated_at'];
    
    public function User(){
        return $this->belongsTo('App\User', 'user_id');
    }
    
    public function Receiver(){
        return $this->belongsTo('App\User', 'receiver_id');
    }
    
    public function Aggregator (){
        return $this->belongsTo('App\Models\Admin', 'trans_to');
    }
    

    public function ExcelTransaction(){
        return $this->belongsTo(ExcelTransaction::Class, 'excel_trans_id');
    }
    
    protected $fillable = [
        'user_id', 'receiver_id', 'company_name', 'add_by','amount', 'transaction_amount','total_amount','billing_description', 'total_amount','fee_pay_by', 'amount_value', 'currency', 'real_value', 'trans_type' ,'refund_status', 'payment_mode', 'refrence_id', 'status','bda_status','receiver_mobile','country_id','walletManagerId','tomember','vouchercode','issuertrxref','acquirertrxref','cardNumber','cardHolderName','senderAccount','receiverAccount','receiverName','senderData','receiverData','remainingWalletBalance','description','trans_for', 'excel_trans_id','entryType','transactionType','orderId','paymentType','onafriq_bda_ids','created_at', 'updated_at','trans_to','notes','referralBy','senderName','senderLastName','senderIDType','senderIDNumber','walletsource','airtelTransId','airtelMoneyId','airtelStatusDescription','merchantCountry','cardType','accountId','referenceInformation','beforeBalance','afterBalance','beforeVirtualBalance','afterVirtualBalance','swapDomainName',
    ];
}

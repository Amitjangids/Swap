<select class="form-control required isWalletManager" name="wallet_manager_id" id="wallet_manager_id">
    <option value="">{{__('message.Select Wallet Manager')}}</option>
    <?php foreach($walletManager as $value) {  ?>
    <option value="{{$value->id}}">{{$value->name}}</option>
    <?php } ?>
</select>
<span><img src="{{PUBLIC_PATH}}/assets/front/images/select-arrow.png" alt="image"></span>
<label>{{__('message.Select Area')}}</label>
{{Form::select('area', $areaList,null, ['class' => 'form-control required','placeholder' => __('message.Select Area')])}}
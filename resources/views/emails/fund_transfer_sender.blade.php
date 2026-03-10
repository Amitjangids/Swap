@extends('emails.emailLayout')
@section('content')
<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff">
                    <tbody>
                        <tr>
                            <td style="padding:25px 20px">


                                <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff">
                                    <tbody>
                                        <tr>
                                            <td style="padding:25px 20px">
                                                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                    <tbody>
                                                        <tr>
                                                            <td style="color:#333333;font-family:'Nunito',sans-serif;font-size:22px;font-weight:bold;line-height:32px;text-align:left;padding-bottom:15px">Hi {{ucfirst($senderName)}},</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color:#333333;font-family:'Nunito',Arial,sans-serif;font-size:18px;font-weight:normal;line-height:24px;text-align:left;padding-bottom:15px"></td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color:#333333;font-family:'Roboto',Arial,sans-serif;font-size:16px;font-weight:normal;line-height:22px;text-align:left;padding-bottom:25px">
                                                            We are writing to confirm that your transfer of {{$currency}}{{$senderAmount}} to {{ucfirst($receiverName)}}'s Swap wallet has been successfully {{$transactionStatus}}. The details of the transaction are as follows:</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color:#333333;font-family:'Roboto',Arial,sans-serif;font-size:16px;font-weight:normal;line-height:22px;text-align:left;padding-bottom:25px">
                                                               <table width="100%" height="100%" align="center" cellspacing="0" cellpadding="10" border="1" style="border-color: #eeeeee1a; background: rgb(226 226 226 / 16%);">
                                                                   <tr>
                                                                       <th>Transaction ID:</th>
                                                                       <td>{{$transId}}</td>
                                                                   </tr>
                                                                   <tr>
                                                                       <th>Transaction Status:</th>
                                                                       <td>{{$transactionStatus}}</td>
                                                                   </tr>
                                                                   <tr>
                                                                       <th>Receiver’s Name:</th>
                                                                       <td>{{ $receiverName ? ucfirst($receiverName) : '' }}
                                                                       </td>
                                                                   </tr>
                                                                   <tr>
                                                                       <th>Amount:</th>
                                                                       <td>{{$currency}}{{ucfirst($senderAmount)}}</td>
                                                                   </tr>
                                                                   <tr>
                                                                       <th>Date & Time:</th>
                                                                       <td>{{$transactionDate}}</td>
                                                                   </tr>
                                                               </table> 
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color:#333333;font-family:'Roboto',Arial,sans-serif;font-size:16px;font-weight:normal;line-height:22px;text-align:left;padding-top:25px">Thanks,<br>The Swap Team</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
    </table>

@endsection


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <style>
		body{margin: 0; padding: 0; background-color: #ccc; font-family:'Open Sans', sans-serif !important;}
        .main-table{background-color: #fff;}
         .main-table tr td{text-align: justify;}
	</style>
</head>
<body>
    <table class="main-table" width="600" border="0" cellpadding="0" cellspacing="0" align="center">
        <tbody>
            <tr>
                <td>
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" style="border:1px solid #eee;">
                        <tbody>
                            <tr style="background:#eee;">
                                <td align="center" height="100" style="text-align: center;"> <a href="#" target="_blank"><img src="{{ PUBLIC_PATH }}/assets/front/images/logo.png" alt="image" width="150px"></a></td>
                            </tr>
                            <tr>
                                <td>
                                    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" style="padding:0;">
                                        <tbody>
                                           <!--  <tr>
                                                <td align="center" colspan="3"><img src="img/closemi-banner-template.png" alt="image"></td>
                                            </tr> -->
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td width="40" height="40">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td align="center" style="font-size:30px; text-align: center; color: #4b2e71; font-weight: 400; line-height: 1.4;">One Time Password(OTP)</td>
                                                <td width="40">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="5">&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td width="40" height="5">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td width="40" height="40">&nbsp;</td>
                                            </tr>
    
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td style="font-size: 22px; color: #000; font-weight: 600; line-height: 1.6;">Dear {{$name}}</td>
                                                <td width="40">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td style="font-size: 16px; color: #000; font-weight: 400; line-height: 1.6;">Thank you for registering with Swap. To complete your registration and verify your email address, please use the following One Time Password (OTP): </td>
                                                <td width="40">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="30">&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td width="40" height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td align="center" style="font-size: 35px; text-align: center; color: #4c2d68; font-weight: 400; line-height: 1.6;"> <span style="background-color: #fff; border:1px solid #4c2d68; padding:14px 70px;">{{$otp}}</span> </td>
                                                <td width="40">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="20">&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td width="40" height="20">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td style="font-size: 16px; color: #000; font-weight: 400; line-height: 1.6; margin: 0 0 10px;">Enter this code on our website to verify your email. This step helps us ensure the security of your account. </td>
                                                <td width="40">&nbsp;</td>
                                            </tr>
                                            
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td style="font-size: 16px; color: #000; font-weight: 400; line-height: 1.2;"> Thank you for choosing Swap.</td>
                                                <td width="40">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="10">&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td width="40" height="10">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td style="font-size: 18px; color: #000; font-weight: 400; line-height: 1.6; margin: 0 0 10px;">Best Regards,</td>
                                                <td width="40">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="40">&nbsp;</td>
                                                <td style="font-size: 18px; color: #000; font-weight: 600; line-height: 1.6; margin: 0 0 10px;">Team Swap</td>
                                                <td width="40">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td width="40" height="10">&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td width="40" height="10">&nbsp;</td>
                                            </tr>
                                            <tr style="background: #4b2e6c">
                                                <td width="40" height="40">&nbsp;</td>
                                                <td align="center" style="font-size: 18px; text-align: center; font-weight: 400; color: #fff;" height="20">© {{date('Y')}} <a style="color: #fff; font-weight:500; font-size:18px; text-decoration:none;" href="{{HTTP_PATH}}" target="_blank"> Swap </a>. All rights reserved</td>
                                                <td width="40" height="40">&nbsp;</td>
                                            </tr>
                                            <tr style="background: #4b2e6c">
                                                <td width="40" height="40">&nbsp;</td>
                                                <td style="font-size: 12px; color: #fff; font-weight: 400; line-height: 1.6; margin: 0 0 10px; font-style: italic;">This email is strictly private and confidential, as it contains information pertaining to clients and/or related parties that is exceptionally sensitive. Disclosure of such information could heavily prejudice Swap and its clients. The contents of this email including any attachments shall not   be shared with, or disclosed to, third parties or other employees of Swap other than to those persons, connected with Swap, who are strictly required to have that information in the course and scope of theirduties.</td>
                                                <td width="40">&nbsp;</td>
                                            </tr>
                                             <tr style="background: #4b2e6c">
                                                <td width="40" height="5">&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td width="40" height="5">&nbsp;</td>
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
</body>
</html>
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
                                                <td
                                                    style="color:#333333;font-family:'Nunito',sans-serif;font-size:22px;font-weight:bold;line-height:32px;text-align:left;padding-bottom:15px">
                                                    Dear {{ $name }},</td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="color:#333333;font-family:'Nunito',Arial,sans-serif;font-size:18px;font-weight:normal;line-height:24px;text-align:left;padding-bottom:15px">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="color:#333333;font-family:'Roboto',Arial,sans-serif;font-size:16px;font-weight:normal;line-height:22px;text-align:left;padding-bottom:25px">
                                                    {{ $subject }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="color:#333333;font-family:'Roboto',Arial,sans-serif;font-size:16px;font-weight:normal;line-height:22px;text-align:left;padding-top:25px">
                                                    Registered Email Address : <b>{{ $email }}</b>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="color:#333333;font-family:'Roboto',Arial,sans-serif;font-size:16px;font-weight:normal;line-height:22px;text-align:left;padding-top:25px">
                                                    Click <a href="{{ $link }}">here</a> to generate the password for your account to login at swap wallet
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="color:#333333;font-family:'Roboto',Arial,sans-serif;font-size:16px;font-weight:normal;line-height:22px;text-align:left;padding-top:25px">
                                                    Thank you for using Swap.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="color:#333333;font-family:'Roboto',Arial,sans-serif;font-size:16px;font-weight:normal;line-height:22px;text-align:left;padding-top:25px">
                                                    Best regards,
                                                    <br>Swap Team
                                                </td>
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

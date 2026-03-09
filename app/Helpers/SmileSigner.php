<?php

namespace App\Helpers;

class SmileSigner
{
    public static function signRequest($method, $host, $path, $body, $accessKey, $secretKey)
    {
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $region = 'us-east-1';
        $service = 'execute-api';

        $canonicalUri = $path;
        $canonicalQueryString = '';
        $canonicalHeaders = "host:$host\nx-amz-date:$amzDate\n";
        $signedHeaders = 'host;x-amz-date';
        $payloadHash = hash('sha256', $body);

        $canonicalRequest = "$method\n$canonicalUri\n$canonicalQueryString\n$canonicalHeaders\n$signedHeaders\n$payloadHash";

        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "$dateStamp/$region/$service/aws4_request";
        $stringToSign = "$algorithm\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);

        $kSecret = 'AWS4' . $secretKey;
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorizationHeader = "$algorithm Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";

        return [
            'Authorization' => $authorizationHeader,
            'x-amz-date' => $amzDate,
            'Content-Type' => 'application/json'
        ];
    }
}

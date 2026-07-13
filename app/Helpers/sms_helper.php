<?php

/**
 * Sms Helper File */

/**
 * Send Sms */
// if( !function_exists('sendSms') )
// {
//     function sendSms($to, $message) {
//         try {
//             // Check the default SMS gateway and instantiate the correct class
//             switch (getSetting('default_sms_gateway')) {
//                 case 'bulksmsbd':
//                     $sms = new App\Libraries\BulkSmsBd();
//                     break;


//                 case 'greenwebsms':
//                     $sms = new App\Libraries\GreenWebSms();
//                     break;


//                 case 'smsq':
//                     $sms = new App\Libraries\SmsQ();
//                     break;


//                 case null:
//                     // Handle the case when the default SMS gateway is null
//                     throw new Exception('No default SMS gateway configured');
//                     break;


//                 default:
//                     throw new Exception('Unsupported SMS gateway');
//             }


//             // Log the message body being sent
//             // log_message('info', 'Updated sendSms($to, $message) message body: ' . $message);


//             // Send the message and capture the result
//             $result = $sms->sendMessage($to, $message);


//             // Return the result
//             return $result;


//         } catch (Exception $e) {
//             // Log the exception message for debugging purposes
//             // log_message('error', 'Error in sendSms: ' . $e->getMessage());
//             // You can return a custom error message or handle it differently
//             return false; // or any other error code/message you prefer
//         }
//     }


// }

if (!function_exists('sendotrSms')) 
{
    if (!function_exists('curl_init')) {
        log_message('error', 'CURL extension is not installed/enabled on this server');
        return ['status' => 'error', 'logs' => 'CURL not available'];
    }
    function sendotrSms($to, $message, $userId = null)
    {
        try {
            $prefix = getSettingPrefixForUser($userId);
            if ($prefix === 'SKIP_SMS') {
                log_message('info', "SMS (OTR) skipped for user ID {$userId} - Reseller lacks SMS permission.");
                return ['status' => 'skipped', 'logs' => 'Reseller does not have SMS permission'];
            }

            switch (getSetting('default_sms_gateway', '', $userId)) {
                case 'bulksmsbd':
                    $sms = new App\Libraries\BulkSmsBd($userId);
                    break;
                case 'bulksmsdhaka':
                    $sms = new App\Libraries\BulkSmsDhaka($userId);
                    break;

                case 'greenwebsms':
                    $sms = new App\Libraries\GreenWebSms($userId);
                    break;

                case 'smsq':
                    $sms = new App\Libraries\SmsQ($userId);
                    break;
                case 'telnet':
                    $sms = new App\Libraries\TelnetSms($userId);
                    break;
                case 'awajdigital':
                    $sms = new App\Libraries\AwajDigital($userId);
                    break;
            }
            log_message('info', 'Updated sendSms($to, $message) message body: ' . $message);

            $result = $sms->sendotrMessage($to, $message);

            return $result;
        }
        catch (Exception $e) {
            // Log the exception message for debugging purposes
            // log_message('error', 'Error in sendSms: ' . $e->getMessage());
            // You can return a custom error message or handle it differently
            return false; // or any other error code/message you prefer
        }
    }
}
/**
 * Check Balance */
if (!function_exists('checkBalance')) 
{
    function checkBalance($gateway)
    {
        if (!function_exists('curl_init')) {
            log_message('error', 'CURL extension is not installed/enabled on this server');
            return ['status' => 'error', 'logs' => 'CURL not available'];
        }

        switch ($gateway) {
            case 'bulksmsbd':
                $sms = new App\Libraries\BulkSmsBd();
                break;
            case 'bulksmsdhaka':
                $sms = new App\Libraries\BulkSmsDhaka();
                break;

            case 'greenwebsms':
                $sms = new App\Libraries\GreenWebSms();
                break;

            case 'smsq':
                $sms = new App\Libraries\SmsQ();
                break;
            case 'telnet':
                $sms = new App\Libraries\TelnetSms();
                break;
            case 'awajdigital':
                $sms = new App\Libraries\AwajDigital();
                break;
        }

        return $sms->checkBalance();
    }
}
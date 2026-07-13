<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AiChatController extends BaseController
{
    public function aiChat()
    {
        // Retrieve frontend JSON payload
        $json = $this->request->getJSON(true) ?? [];

        /* 1. Identity comes from the SERVER SESSION ONLY.
           This used to read $json['user_id'] / $json['user_role'] from the request
           body first, falling back to the session. POST /api/chat is a public,
           CSRF-excepted route, so anyone could send
           {"user_id":1,"user_role":"super_admin"} and sail through the
           userHasPermission() check below (which grants everything to
           super_admin), then drive the chat backend as that user. Never let a
           caller name their own identity or role. */
        $userId   = getSession('user_id');
        $userRole = getSession('user_role');

        if (!$userId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'User session or ID not found.'
            ])->setStatusCode(401);
        }

        // Re-derive the role from the database rather than trusting anything the
        // client sent; the session role is server-set, so it is only a fallback.
        if (!$userRole) {
            $userModel = model('App\Models\User');
            $user = $userModel->find($userId);
            if ($user) {
                $userRole = is_object($user) ? ($user->role ?? null) : ($user['role'] ?? null);
            }
        }

        // 2. Verify user permission
        if (!userHasPermission('ai_chat', 'chat', $userRole, $userId)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized access.'
            ])->setStatusCode(403);
        }

        $message = $json['message'] ?? '';
        $sessionId = $json['session_id'] ?? null;

        if (empty($message)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Message is required.'
            ])->setStatusCode(400);
        }

        // Determine language: check JSON payload first, then locale, then session
        $language = $json['language'] ?? null;
        if (empty($language)) {
            $locale = $this->request->getLocale();
            $language = (strtolower($locale) === 'bn' || getSession('lang') === 'bn') ? 'BN' : 'EN';
        }
        $language = strtoupper($language);


        // 3. Prepare payload for the external API
        $payload = [
            'user_id' => (int) $userId,
            'message' => $message,
            'context_count' => 10,
            'language' => $language
        ];

        if ($sessionId !== null && $sessionId !== '') {
            $payload['session_id'] = $sessionId;
        }

        // 4. Send request to external AI API
        $url = 'http://203.18.158.157:8062/api/chat';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 seconds timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // 5. Handle response and errors
        if ($response === false || $httpCode !== 200) {
            log_message('error', "AI Chat API error: Code {$httpCode}, Error: {$curlError}, Response: {$response}");
            
            // Return a friendly, localized fallback mock response if the API is offline
            $mock = $this->getMockResponse($message, $language, $sessionId);
            return $this->response->setJSON($mock);
        }

        // Parse and return external response
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid response from AI server.',
                'response' => 'Sorry, I received an invalid response from the AI server.'
            ]);
        }

        return $this->response->setJSON($responseData);
    }

    /**
     * Generate structured mock replies based on suggestions keywords in both English and Bangla.
     */
    private function getMockResponse($message, $language, $sessionId)
    {
        $msg = strtolower(trim($message));
        $isBn = (strtoupper($language) === 'BN');

        $response = '';

        if (strpos($msg, 'dhcp') !== false || strpos($msg, 'কনফিগারেশন') !== false) {
            $response = $isBn 
                ? "আপনার রাউটারে DHCP কনফিগার করতে:\n১. আপনার রাউটার প্যানেলে লগইন করুন।\n২. Network > DHCP Server অপশনে যান।\n৩. DHCP Server সক্রিয় (Enable) করুন এবং আইপি রেঞ্জ সেট করুন।\n৪. সেভ করে রাউটারটি রিবুট করুন।"
                : "To configure DHCP on your router:\n1. Log in to your router settings panel.\n2. Navigate to Network Settings > DHCP Server.\n3. Enable the DHCP Server and define the IP Address Pool/Range.\n4. Save settings and restart your router.";
        } 
        elseif (strpos($msg, 'band') !== false || strpos($msg, 'limit') !== false || strpos($msg, 'ব্যান্ড') !== false || strpos($msg, 'ম্যানেজমেন্ট') !== false || strpos($msg, 'brand') !== false) {
            $response = $isBn
                ? "ব্যান্ডউইথ ম্যানেজমেন্ট ইন্টারনেট স্পিড নিয়ন্ত্রণ করতে সাহায্য করে। আমাদের আইএসপি প্যানেলে, আপনি প্যাকেজ তৈরি করার সময় অথবা গ্রাহক সংযোগ বিবরণীর অধীনে আপলোড/ডাউনলোড স্পিড লিমিট সেট করতে পারেন।"
                : "Bandwidth Management allows controlling client speeds. In our ISP dashboard, you can define maximum upload/download limits under Package Creation or within individual Customer Connection details.";
        }
        elseif (strpos($msg, 'channel') !== false || strpos($msg, 'optim') !== false || strpos($msg, 'চ্যানেল') !== false || strpos($msg, 'অপ্টিমাইজ') !== false) {
            $response = $isBn
                ? "আপনার ওয়াইফাই চ্যানেল অপ্টিমাইজ করতে:\n১. রাউটার সেটিংস পেজ খুলুন।\n২. Wireless settings অপশনে যান।\n৩. সিগন্যাল ওভারল্যাপ এবং ইন্টারফারেন্স এড়াতে চ্যানেল Auto থেকে পরিবর্তন করে ১, ৬ অথবা ১১ সিলেক্ট করুন।\n৪. ভালো স্থায়িত্বের জন্য চ্যানেল উইডথ ২০ মেগাহার্জ সেট করুন।"
                : "To optimize your Wi-Fi channel:\n1. Open your router setup page.\n2. Navigate to Wireless Settings.\n3. Change the Channel from Auto to 1, 6, or 11 (for 2.4GHz) to minimize frequency overlap.\n4. Set Channel Width to 20MHz for better stability.";
        }
        elseif (strpos($msg, 'movie') !== false || strpos($msg, 'ftp') !== false || strpos($msg, 'মুভি') !== false || strpos($msg, 'সার্ভার') !== false) {
            $response = $isBn
                ? "আমাদের নেটওয়ার্কে উপলব্ধ জনপ্রিয় এফটিপি/মুভি সার্ভারগুলোর তালিকা:\n১. SamFTP (samftp.com)\n২. CircleFTP (circleftp.net)\n৩. DiscoveryFTP (discoveryftp.com)\nউচ্চ গতিতে মুভি স্ট্রিমিং এবং ডাউনলোডের জন্য এগুলো ব্যবহার করুন!"
                : "Here are the popular FTP/Movie servers available on our network:\n1. SamFTP (samftp.com)\n2. CircleFTP (circleftp.net)\n3. DiscoveryFTP (discoveryftp.com)\nUse these for high-speed local streaming and downloads!";
        }
        else {
            $response = $isBn
                ? "হ্যালো! আমি আপনার এআই অ্যাসিস্ট্যান্ট। আমি আপনাকে DHCP কনফিগারেশন, ব্যান্ডউইথ ম্যানেজমেন্ট, চ্যানেল অপ্টিমাইজেশন অথবা মুভি সার্ভার খুঁজে পেতে সাহায্য করতে পারি। আপনি কি জানতে চান?"
                : "Hello! I am your AI Assistant. I can help you with DHCP configuration, bandwidth management, channel optimization, or finding movie servers on our network. What would you like to know?";
        }

        return [
            'status' => 'success',
            'response' => $response,
            'session_id' => $sessionId ?? 'mock-session-' . rand(1000, 9999)
        ];
    }
}

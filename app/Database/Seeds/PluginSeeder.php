<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PluginSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'title'         => 'Bongo OTT',
                'category'      => 'VAS',
                'description'   => 'This is an ISP Digital Plugin that allows ISPs to directly subscribe to Bongo OTT from their dashboard.',
                'price_type'    => 'Free',
                'billing_cycle' => 'monthly',
                'image'         => 'assets/img/plugins_images/bongo.png',
                'status'        => 1,
            ],
            [
                'title'         => 'Digital Square',
                'category'      => 'Hardware Integration',
                'description'   => 'Digital Square acts as a connected hardware catalog inside ISP Digital, helping ISPs quickly find essential network devices.',
                'price_type'    => 'Free',
                'billing_cycle' => 'lifetime',
                'image'         => 'assets/img/plugins_images/digital_square.png',
                'status'        => 1,
            ],
            [
                'title'         => 'SSLCommerz',
                'category'      => 'Payment Gateway (API)',
                'description'   => 'The largest payment gateway aggregator in Bangladesh.',
                'price_type'    => 'Paid',
                'billing_cycle' => 'monthly',
                'image'         => 'assets/img/plugins_images/sslcommerz.png',
                'status'        => 1,
            ],
            [
                'title'         => 'EPS',
                'category'      => 'Payment Gateway (API)',
                'description'   => 'Easy Payment System — accept cards, MFS and net banking across 18+ industries in Bangladesh.',
                'price_type'    => 'Paid',
                'billing_cycle' => 'monthly',
                'image'         => 'assets/img/methods/eps.svg',
                'status'        => 1,
            ],
            [
                'title'         => 'shurjoPay',
                'category'      => 'Payment Gateway (API)',
                'description'   => 'shurjoPay by Shurjomukhi Limited — a secure Bangladeshi payment gateway supporting cards and mobile financial services.',
                'price_type'    => 'Paid',
                'billing_cycle' => 'monthly',
                'image'         => 'assets/img/methods/shurjopay.svg',
                'status'        => 1,
            ],
            [
                'title'         => 'PayStation',
                'category'      => 'Payment Gateway (API)',
                'description'   => 'PayStation — a licensed Bangladeshi Payment System Operator supporting MFS, cards and net banking.',
                'price_type'    => 'Paid',
                'billing_cycle' => 'monthly',
                'image'         => 'assets/img/methods/paystation.svg',
                'status'        => 1,
            ],
            [
                'title'         => 'SMS Global',
                'category'      => 'SMS Gateway API',
                'description'   => 'Integrate SMS Global for worldwide text message delivery.',
                'price_type'    => 'Paid',
                'billing_cycle' => 'monthly',
                'image'         => 'assets/img/plugins_images/sms_global.png',
                'status'        => 1,
            ],
            [
                'title'         => 'Client Mobile App',
                'category'      => "Mobile Application's",
                'description'   => 'Full featured Android & iOS app for your end users.',
                'price_type'    => 'Paid',
                'billing_cycle' => 'monthly',
                'image'         => 'assets/img/plugins_images/mobile_app.png',
                'status'        => 1,
            ],
        ];

        $model = new \App\Models\PluginModel();
        foreach ($data as $plugin) {
            $model->insert($plugin);
        }
    }
}

<?php

namespace Zapi\Modules\Shared\Rewards\Support;

/**
 * Bangla notification copy for referral / reward events.
 * The app and backend both standardise on Bangla for customer-facing text.
 */
final class RewardMessages
{
    public static function referralRegistered(string $refereeName): array
    {
        return [
            'title' => 'নতুন রেফারেল নিবন্ধন',
            'body'  => "আপনার রেফারেল কোড দিয়ে {$refereeName} নিবন্ধন করেছেন। অনুমোদনের পর আপনি পয়েন্ট পাবেন।",
        ];
    }

    public static function referralVerified(string $refereeName, int $points): array
    {
        return [
            'title' => 'রেফারেল অনুমোদিত হয়েছে',
            'body'  => "অভিনন্দন! আপনার রেফার করা গ্রাহক {$refereeName} সক্রিয় হয়েছে। আপনি {$points} রিওয়ার্ড পয়েন্ট পেয়েছেন।",
        ];
    }

    public static function referralRejected(string $refereeName): array
    {
        return [
            'title' => 'রেফারেল প্রত্যাখ্যাত',
            'body'  => "দুঃখিত, {$refereeName}-এর রেফারেলটি অনুমোদিত হয়নি।",
        ];
    }

    public static function pointsEarned(int $points, string $reasonBn): array
    {
        return [
            'title' => 'রিওয়ার্ড পয়েন্ট অর্জিত',
            'body'  => "আপনি {$reasonBn} বাবদ {$points} রিওয়ার্ড পয়েন্ট পেয়েছেন।",
        ];
    }

    public static function pointsRedeemed(int $points, int $bdt): array
    {
        return [
            'title' => 'রিওয়ার্ড পয়েন্ট ব্যবহৃত',
            'body'  => "আপনি {$points} পয়েন্ট ব্যবহার করে ৳{$bdt} ছাড় পেয়েছেন।",
        ];
    }

    /** Human-readable Bangla label for a reward source (used in messages). */
    public static function reasonForSource(string $source): string
    {
        $map = [
            RewardSources::REFERRAL       => 'সফল রেফারেল',
            RewardSources::EARLY_RENEWAL  => 'সময়ের আগে রিনিউ',
            RewardSources::STREAK         => 'নিয়মিত সময়মতো পেমেন্ট',
            RewardSources::LOYALTY_6M     => '৬ মাসের লয়্যালটি',
            RewardSources::LOYALTY_12M    => '১২ মাসের লয়্যালটি',
            RewardSources::UPGRADE        => 'প্যাকেজ আপগ্রেড',
            RewardSources::ONLINE_PAYMENT => 'অনলাইন পেমেন্ট',
            RewardSources::AUTOPAY        => 'অটো-পে চালু',
            RewardSources::FEEDBACK       => 'ফিডব্যাক প্রদান',
            RewardSources::TICKET_RATING  => 'সাপোর্ট রেটিং',
            RewardSources::BIRTHDAY       => 'জন্মদিনের উপহার',
        ];
        return $map[$source] ?? 'রিওয়ার্ড';
    }
}

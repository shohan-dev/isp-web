<?php

namespace Zapi\Modules\Shared\Rewards\Support;

/**
 * Canonical string constants for the Referral & Reward Point system.
 *
 * Keeping these in one place avoids typo-driven bugs across the engine,
 * the reconciler, the cron jobs and the portal services. The values are
 * persisted to the database (reward_transactions.source, referrals.status,
 * etc.) so they MUST stay stable once shipped.
 */
final class RewardSources
{
    // ---- Reward-transaction sources (why points moved) ----------------
    public const REFERRAL        = 'referral';
    public const EARLY_RENEWAL   = 'early_renewal';
    public const STREAK          = 'streak';            // 3 consecutive on-time payments
    public const LOYALTY_6M      = 'loyalty_6m';
    public const LOYALTY_12M     = 'loyalty_12m';
    public const UPGRADE         = 'upgrade';           // annual / higher package
    public const ONLINE_PAYMENT  = 'online_payment';
    public const AUTOPAY         = 'autopay';
    public const FEEDBACK        = 'feedback';
    public const TICKET_RATING   = 'ticket_rating';
    public const BIRTHDAY        = 'birthday';
    public const REDEMPTION      = 'redemption';        // negative — points spent
    public const EXPIRY          = 'expiry';            // negative — points expired
    public const MANUAL_ADJUST   = 'manual_adjust';

    // ---- Ledger row status -------------------------------------------
    public const TXN_POSTED   = 'posted';
    public const TXN_REVERSED = 'reversed';

    // ---- Referral lifecycle ------------------------------------------
    public const REFERRAL_PENDING  = 'pending';
    public const REFERRAL_VERIFIED = 'verified';
    public const REFERRAL_REJECTED = 'rejected';
    public const REFERRAL_FLAGGED  = 'flagged';

    // ---- Redemption hold lifecycle -----------------------------------
    public const REDEEM_HELD     = 'held';
    public const REDEEM_APPLIED  = 'applied';
    public const REDEEM_RELEASED = 'released';
    public const REDEEM_EXPIRED  = 'expired';

    // ---- Config keys (reward_settings.key_name) ----------------------
    public const KEY_REFERRAL_POINTS       = 'referral_points';
    public const KEY_EARLY_RENEWAL_POINTS  = 'early_renewal_points';
    public const KEY_STREAK_POINTS         = 'streak_points';
    public const KEY_LOYALTY_6M_POINTS     = 'loyalty_6m_points';
    public const KEY_LOYALTY_12M_POINTS    = 'loyalty_12m_points';
    public const KEY_UPGRADE_POINTS        = 'upgrade_points';
    public const KEY_ONLINE_PAYMENT_POINTS = 'online_payment_points';
    public const KEY_AUTOPAY_POINTS        = 'autopay_points';
    public const KEY_FEEDBACK_POINTS       = 'feedback_points';
    public const KEY_TICKET_RATING_POINTS  = 'ticket_rating_points';
    public const KEY_BIRTHDAY_POINTS       = 'birthday_points';
    public const KEY_POINT_VALUE_BDT       = 'point_value_bdt';
    public const KEY_POINT_EXPIRY_DAYS     = 'point_expiry_days';
    public const KEY_MAX_REDEEM_PERCENT    = 'max_redeem_percent';
    public const KEY_REFERRAL_ENABLED      = 'referral_enabled';
    public const KEY_REDEMPTION_ENABLED    = 'redemption_enabled';
    public const KEY_FEEDBACK_MONTHLY_CAP  = 'feedback_monthly_cap';

    // ---- Notification types (app_notifications.type) -----------------
    public const NOTIFY_REWARD   = 'reward';
    public const NOTIFY_REFERRAL = 'referral';
    public const NOTIFY_SYSTEM   = 'system';

    /**
     * Payment gateways that count as "online payment" for the +online bonus.
     * Matched case-insensitively against payments.paid_via.
     */
    public const ONLINE_GATEWAYS = ['bkash', 'nagad', 'rocket', 'sslcommerz', 'card', 'eps', 'shurjopay', 'paystation'];
}

<?php /* Multi-Tier User Structure — real ISP Pay BD roles (5 tiers) as a connected
         org chart: vertical spine + a branch to the Staff/Reseller pair + a merge
         to the Customer, premium node cards with staggered entrance animation. */ ?>
<section class="lp-section lp-section--light" id="lp-reseller" data-lp-section>
    <div class="lp-container">
        <div class="lp-hierarchy-layout">

            <!-- Left: intro + permission facts -->
            <aside class="lp-hierarchy-aside lp-reveal">
                <span class="lp-section__label">User Hierarchy</span>
                <h2 class="lp-section__title">A multi-tier user structure &mdash; every level locked to its own scope.</h2>
                <p class="lp-section__desc">From a single Super Admin down to every subscriber, each level gets its own panel, its own permissions, and full control over its own scope. Nobody sees a rupee more than their role allows.</p>

                <div class="lp-hierarchy-perms">
                    <div class="lp-hierarchy-perm">
                        <span class="lp-hierarchy-perm__ic"><i class="fas fa-layer-group"></i></span>
                        <p><b>24</b> permission modules, with CRUD on each</p>
                    </div>
                    <div class="lp-hierarchy-perm">
                        <span class="lp-hierarchy-perm__ic"><i class="fas fa-user-pen"></i></span>
                        <p><b>Per-user</b> override any one person &mdash; no new role to invent</p>
                    </div>
                    <div class="lp-hierarchy-perm">
                        <span class="lp-hierarchy-perm__ic"><i class="fas fa-shield-halved"></i></span>
                        <p><b>Scoped</b> staff, resellers &amp; customers each see only their own tree</p>
                    </div>
                </div>
            </aside>

            <!-- Right: the connected tree -->
            <div class="lp-hierarchy lp-stagger-children lp-reveal">

                <!-- Tier 1 — platform owner -->
                <div class="lp-hierarchy__row lp-hierarchy__row--single lp-reveal-child">
                    <div class="lp-roles-card lp-roles-card--accent">
                        <div class="lp-roles-card__head">
                            <div class="lp-roles-card__icon"><i class="fas fa-crown"></i></div>
                            <span class="lp-roles-card__tag">Platform owner</span>
                        </div>
                        <h3>Super Admin</h3>
                        <p>Full platform control. Provisions and manages every ISP on the system &mdash; global plans, branding, and settings.</p>
                    </div>
                </div>

                <div class="lp-hconn lp-reveal-child" aria-hidden="true"><span></span></div>

                <!-- Tier 2 — the operator / ISP account -->
                <div class="lp-hierarchy__row lp-hierarchy__row--single lp-reveal-child">
                    <div class="lp-roles-card">
                        <div class="lp-roles-card__head">
                            <div class="lp-roles-card__icon"><i class="fas fa-building"></i></div>
                            <span class="lp-roles-card__tag">Multi-branch &middot; your account</span>
                        </div>
                        <h3>Operator (Admin)</h3>
                        <p>The ISP account. Full control of packages, MikroTik &amp; OLT, billing rules, and multiple company branches &mdash; plus every permission below.</p>
                    </div>
                </div>

                <!-- Branch: Operator splits to Staff + Reseller -->
                <div class="lp-hbranch lp-reveal-child" aria-hidden="true">
                    <svg viewBox="0 0 100 34" preserveAspectRatio="none">
                        <path d="M50 1 V10 M25 10 H75 M25 10 V33 M75 10 V33" fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linecap="round"/>
                    </svg>
                </div>

                <!-- Tier 3 — staff & resellers, side by side -->
                <div class="lp-hierarchy__row lp-hierarchy__row--pair lp-reveal-child">
                    <div class="lp-roles-card">
                        <div class="lp-roles-card__head">
                            <div class="lp-roles-card__icon"><i class="fas fa-user-tie"></i></div>
                            <span class="lp-roles-card__tag">24 modules &middot; CRUD</span>
                        </div>
                        <h3>Employee</h3>
                        <p>Scoped team logins &mdash; give support read-only tickets or a collector payments-only, without handing out full admin rights.</p>
                    </div>
                    <div class="lp-roles-card">
                        <div class="lp-roles-card__head">
                            <div class="lp-roles-card__icon"><i class="fas fa-store"></i></div>
                            <span class="lp-roles-card__tag">Branded &middot; auto commission</span>
                        </div>
                        <h3>Reseller</h3>
                        <p>Their own logo, Bangla app, customers, and balance. Commission splits automatically the moment a bKash or Nagad payment settles.</p>
                    </div>
                </div>

                <!-- Merge: the pair converges onto the Customer -->
                <div class="lp-hbranch lp-hbranch--merge lp-reveal-child" aria-hidden="true">
                    <svg viewBox="0 0 100 34" preserveAspectRatio="none">
                        <path d="M25 1 V13 M75 1 V13 M25 13 H75 M50 13 V33" fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linecap="round"/>
                    </svg>
                </div>

                <!-- Tier 4 — the subscriber -->
                <div class="lp-hierarchy__row lp-hierarchy__row--single lp-reveal-child">
                    <div class="lp-roles-card">
                        <div class="lp-roles-card__head">
                            <div class="lp-roles-card__icon"><i class="fas fa-user"></i></div>
                            <span class="lp-roles-card__tag">Self-service</span>
                        </div>
                        <h3>Customer</h3>
                        <p>A Bangla app to pay via bKash/Nagad, check data usage, and open a ticket. Zero access to billing, network, or other customers.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

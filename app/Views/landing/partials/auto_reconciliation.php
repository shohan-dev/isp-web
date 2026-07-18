<section class="lp-section lp-section--light" id="lp-auto-reconcile" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Flagship Feature</span>
            <h2 class="lp-section__title">
                <span class="lp-lang lp-lang--en">Every bKash &amp; Nagad payment, matched to the right customer in under a second.</span>
                <span class="lp-lang lp-lang--bn" hidden>প্রতিটি বিকাশ ও নগদ পেমেন্ট — সঠিক গ্রাহকের সাথে এক সেকেন্ডেরও কমে মিলিয়ে নেওয়া।</span>
            </h2>
            <p class="lp-section__desc">
                <span class="lp-lang lp-lang--en">The payment SMS lands — we read the TrxID and mobile number, find the subscriber, clear the invoice, and tell MikroTik to reconnect. No month-end spreadsheet, no manual matching, no angry call about a payment that already arrived.</span>
                <span class="lp-lang lp-lang--bn" hidden>পেমেন্ট এসএমএস এলেই সিস্টেম TrxID ও মোবাইল নম্বর পড়ে, সাবস্ক্রাইবার খুঁজে বের করে, বিল ক্লিয়ার করে এবং মাইক্রোটিককে রিকানেক্ট করতে বলে। মাস শেষে স্প্রেডশিট নেই, ম্যানুয়াল মিলানো নেই, আগেই আসা পেমেন্ট নিয়ে রাগী কল নেই।</span>
            </p>
        </div>
        <div class="lp-reconcile-flow lp-stagger-children lp-reveal">
            <div class="lp-reconcile-step lp-reveal-child">
                <div class="lp-reconcile-step__ix">01 · ingest</div>
                <h3 class="lp-reconcile-step__title">The payment SMS arrives</h3>
                <p class="lp-reconcile-step__desc">Send Money or payment hits your merchant number — or the subscriber pays inside the branded app.</p>
                <div class="lp-reconcile-step__metric"><span class="lp-recon-dot"></span>read in real time</div>
            </div>
            <div class="lp-reconcile-step lp-reveal-child lp-reveal-delay-1">
                <div class="lp-reconcile-step__ix">02 · match</div>
                <h3 class="lp-reconcile-step__title">Fingerprinted to a subscriber</h3>
                <p class="lp-reconcile-step__desc">We match the TrxID and payer number against your subscriber list — edge cases get flagged for one-tap review.</p>
                <div class="lp-reconcile-step__metric"><b>98%+</b>&nbsp;matched first try</div>
            </div>
            <div class="lp-reconcile-step lp-reveal-child lp-reveal-delay-2">
                <div class="lp-reconcile-step__ix">03 · reconnect</div>
                <h3 class="lp-reconcile-step__title">Paid, extended, back online</h3>
                <p class="lp-reconcile-step__desc">The invoice closes, expiry rolls forward, and PPPoE reconnects on the router — nobody SSHes into anything.</p>
                <div class="lp-reconcile-step__metric"><b>~0.8s</b>&nbsp;end to end</div>
            </div>
        </div>
    </div>
</section>

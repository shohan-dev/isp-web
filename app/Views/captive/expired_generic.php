<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Internet Expired</title>

<!-- 08 §2(b) / 08 §10 — Google Fonts CDN removed: this is the exact page
     served WHEN THE CUSTOMER'S INTERNET IS CUT OFF, so a CDN font request
     here can never succeed for the page's actual use case. Falls back to
     whatever Bengali-capable font the device already has installed (Noto
     Sans Bengali / Hind Siliguri ship on most Android/Windows builds) —
     TODO(08 §10): self-host a real Bengali woff2 face once one is vendored
     into this repo (not possible in this pass — no external-fetch capability). -->
<!-- 08 §2(b) — decided theme-exempt: base.css's body.ipb rule sets a
     literal font-family that would out-specificity and undo the Bengali
     font-stack fix directly above. Self-contained inline styles stay
     authoritative on a page that must render with the WAN down. -->
<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Noto Sans Bengali','Hind Siliguri','SolaimanLipi',sans-serif;
}

body{
min-height:100vh;
min-height:100dvh;
display:flex;
align-items:center;
justify-content:center;
background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);
color:#fff;
padding:20px;
}

/* Card */

.card{
width:100%;
max-width:420px;
background:rgba(255,255,255,0.08);
backdrop-filter:blur(20px);
border-radius:18px;
padding:35px 28px;
text-align:center;
box-shadow:var(--shadow-3, 0 10px 40px rgba(0,0,0,0.4));
animation:fadeIn .6s ease;
}

/* Title */

.title{
font-size:26px;
font-weight:700;
margin-bottom:6px;
}

.subtitle{
font-size:14px;
opacity:.8;
margin-bottom:25px;
}

/* Alert */

.alert{
background:rgba(255,0,0,0.15);
border:1px solid rgba(255,80,80,.6);
padding:15px;
border-radius:12px;
margin-bottom:20px;
font-size:15px;
}

.alert strong{
color:#ff8080;
}

/* Info */

.info{
background:rgba(255,255,255,.08);
padding:12px;
border-radius:10px;
font-size:14px;
margin-bottom:25px;
}

/* Pay Button */

.pay-btn{
width:100%;
padding:14px;
font-size:16px;
font-weight:600;
border:none;
border-radius:10px;
background:linear-gradient(90deg,#00c853,#43a047);
color:white;
cursor:pointer;
transition:.3s;
}

.pay-btn:hover{
transform:scale(1.03);
box-shadow:var(--shadow-2, 0 5px 20px rgba(0,0,0,.3));
}

/* number */

.number-box{
background:#0b1d30;
padding:12px;
border-radius:10px;
margin-top:20px;
display:flex;
justify-content:space-between;
align-items:center;
font-size:15px;
}

.copy{
background:#1e88e5;
border:none;
padding:6px 10px;
border-radius:6px;
cursor:pointer;
color:white;
font-size:12px;
}

/* note */

.note{
font-size:13px;
opacity:.8;
margin-top:15px;
}

/* animation */

@keyframes fadeIn{
from{
opacity:0;
transform:translateY(10px);
}
to{
opacity:1;
transform:translateY(0);
}
}

/* This page's ONLY job on a phone is: read the number, tap Copy, pay via
   bKash/Nagad. The .copy button was a 6px/10px pill (~24px tall) — well
   under a thumb-safe tap target on the exact screen every cut-off customer
   is stuck on. Widen it on phones only; colors are inherited from .copy. */
@media (max-width: 767px){
.copy{
padding:10px 16px;
font-size:13px;
min-height:44px;
display:inline-flex;
align-items:center;
justify-content:center;
}
}

@media(max-width:480px){

.card{
padding:25px 20px;
}

.title{
font-size:22px;
}

}

</style>
</head>

<body>

<div class="card">

<div class="title">ISPPAYBD </div>
<div class="subtitle">Fast & Reliable Internet Service</div>

<div class="alert">
<strong>⚠ আপনার ইন্টারনেট মেয়াদ শেষ</strong><br>
দয়া করে বিল পরিশোধ করুন।
</div>

<div class="info">
পেমেন্ট করার পর সাথে সাথে ইন্টারনেট চালু হয়ে যাবে।
</div>

<button class="pay-btn" onclick="payNow()">
💳 Pay Now
</button>

<div class="number-box">
<span id="number">01623237729</span>
<button class="copy" onclick="copyNumber()">Copy</button>
</div>

<div class="note">
bKash / Nagad এ পেমেন্ট করতে উপরের নাম্বার ব্যবহার করুন
</div>

</div>

<script>

function copyNumber(){

let number=document.getElementById("number").innerText;

// 01 §4.14 — navigator.clipboard requires a secure context (HTTPS/localhost)
// and THROWS on captive-portal HTTP. Fall back to the older execCommand path,
// which works everywhere the Clipboard API can't.
if (navigator.clipboard && window.isSecureContext) {
  navigator.clipboard.writeText(number).then(function () {
    alert("Number Copied");
  }).catch(function () {
    legacyCopy(number);
  });
} else {
  legacyCopy(number);
}

function legacyCopy(text) {
  var ta = document.createElement("textarea");
  ta.value = text;
  ta.style.position = "fixed";
  ta.style.opacity = "0";
  document.body.appendChild(ta);
  ta.focus();
  ta.select();
  try {
    document.execCommand("copy");
    alert("Number Copied");
  } catch (e) {
    alert("Could not copy automatically — number is " + text);
  }
  document.body.removeChild(ta);
}

}

function payNow(){

// 01 §4.14 / 08 §8 — was a dead placeholder domain ("https://your-payment-link.com")
// that NO expired customer could ever pay through — a revenue bug wearing a
// UI costume. The controller (CaptivePortal / zapi CaptivePortalController)
// already resolves this customer's pending Payment row and passes payment_id
// to this view; route to the real, public, no-login payment page.
window.location.href = "<?= route_to('route.payment.pay', (int) ($payment_id ?? 0)); ?>";

}

</script>

</body>
</html>
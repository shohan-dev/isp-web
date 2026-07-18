<script>
(function () {
  var body = document.body;
  var tenantOnly = body.getAttribute('data-tenant-only') === '1';
  var loginPath = body.getAttribute('data-login-url') || '/auth/login';
  var registerPath = body.getAttribute('data-register-url') || '/auth/registration';
  var titles = { login: 'Sign in', register: 'Start free trial' };

  function normalizePath(url) {
    try {
      return new URL(url, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    } catch (e) {
      return '';
    }
  }

  var loginNorm = normalizePath(loginPath);
  var registerNorm = normalizePath(registerPath);

  function setMode(mode, push) {
    if (tenantOnly) return;
    var isRegister = mode === 'register';
    body.classList.toggle('is-register', isRegister);
    body.setAttribute('data-auth-mode', mode);
    document.title = (titles[mode] || 'Auth') + ' | <?= esc($brandTitle ?? 'ISP Pay BD'); ?>';

    var loginPane = document.querySelector('.ipb-auth-pane--login');
    var registerPane = document.querySelector('.ipb-auth-pane--register');
    var brandLeft = document.querySelector('.ipb-auth-pane--brand-left');
    var brandRight = document.querySelector('.ipb-auth-pane--brand-right');
    if (loginPane) loginPane.setAttribute('aria-hidden', isRegister ? 'true' : 'false');
    if (registerPane) registerPane.setAttribute('aria-hidden', isRegister ? 'false' : 'true');
    if (brandLeft) brandLeft.setAttribute('aria-hidden', isRegister ? 'false' : 'true');
    if (brandRight) brandRight.setAttribute('aria-hidden', isRegister ? 'true' : 'false');

    if (push !== false) {
      var url = isRegister ? registerPath : loginPath;
      if (window.location.pathname.replace(/\/+$/, '') !== normalizePath(url)) {
        history.pushState({ authMode: mode }, '', url);
      }
    }

    if (isRegister) {
      var regPane = document.querySelector('.ipb-auth-pane--register');
      if (regPane) regPane.scrollTop = 0;
      hideRegisteredAlert();
    } else {
      var loginCol = document.querySelector('.ipb-auth-pane--login');
      if (loginCol) loginCol.scrollTop = 0;
    }
  }

  function modeFromPath() {
    var path = window.location.pathname.replace(/\/+$/, '') || '/';
    if (path === registerNorm) return 'register';
    return 'login';
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('.ipb-auth-switch, a[data-auth-mode]');
    if (!link || tenantOnly) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey) return;
    e.preventDefault();
    setMode(link.getAttribute('data-auth-mode') || 'login');
  });

  window.addEventListener('popstate', function () {
    if (tenantOnly) return;
    setMode(modeFromPath(), false);
    if (modeFromPath() === 'login' && hasRegisteredQuery()) {
      showRegisteredAlert(new URLSearchParams(window.location.search).get('pending') === '1');
      clearRegisteredQuery();
    } else if (modeFromPath() !== 'login') {
      hideRegisteredAlert();
    }
  });

  function hideRegisteredAlert() {
    var el = document.getElementById('loginRegisteredAlert');
    if (!el) return;
    el.hidden = true;
    el.setAttribute('aria-hidden', 'true');
  }

  function showRegisteredAlert(pending) {
    var el = document.getElementById('loginRegisteredAlert');
    if (!el) return;
    var text = document.getElementById('loginRegisteredAlertText');
    if (text) {
      // The outcome is the title ("Registration submitted"); this line is only the
      // next step, so it must not restate it.
      var msg = 'Sign in below with your email and password.';
      if (pending) {
        msg += ' Your account may stay inactive until approval is complete.';
      }
      text.textContent = msg;
    }
    el.hidden = false;
    el.setAttribute('aria-hidden', 'false');
  }

  function hasRegisteredQuery() {
    return new URLSearchParams(window.location.search).get('registered') === '1';
  }

  function clearRegisteredQuery() {
    if (!hasRegisteredQuery()) return;
    var url = new URL(window.location.href);
    url.searchParams.delete('registered');
    url.searchParams.delete('pending');
    history.replaceState({ authMode: 'login' }, '', url.pathname + (url.search || ''));
  }

  if (hasRegisteredQuery()) {
    setMode('login', false);
    showRegisteredAlert(new URLSearchParams(window.location.search).get('pending') === '1');
    clearRegisteredQuery();
  } else {
    hideRegisteredAlert();
    setMode(body.getAttribute('data-auth-mode') || modeFromPath(), false);
  }

  window.ipbAuthGate = { setMode: setMode, showRegisteredAlert: showRegisteredAlert };

  /* Password toggle */
  var togglePassword = document.getElementById('toggle-password');
  var passwordInput = document.getElementById('login_password');
  if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', function () {
      var type = passwordInput.type === 'password' ? 'text' : 'password';
      passwordInput.type = type;
      var icon = togglePassword.querySelector('i');
      icon.classList.toggle('fa-eye', type === 'password');
      icon.classList.toggle('fa-eye-slash', type !== 'password');
    });
  }

  var userParam = new URLSearchParams(window.location.search).get('user');
  if (userParam) {
    var emailInput = document.getElementById('login_email');
    if (emailInput) emailInput.value = decodeURIComponent(userParam);
  }

  /* Demo deep-link (?user=&pass= from the landing "Try It" cards) — prefill the
     password too so the visitor just presses Sign in. Demo accounts only. */
  var passParam = new URLSearchParams(window.location.search).get('pass');
  if (passParam) {
    var demoPassInput = document.getElementById('login_password');
    if (demoPassInput) demoPassInput.value = decodeURIComponent(passParam);
  }

  /* Login AJAX */
  var loginValidateUrl = '<?= route_to("route.auth.login.validate"); ?>';

  /* How long the success state is shown before the redirect fires. The progress bar
     is driven off this same number (via --ipb-auth-redirect), so the bar can never
     disagree with the actual wait. */
  var LOGIN_REDIRECT_MS = 1200;

  /**
   * The status block inside the auth card (.ipb-auth-status, auth.css).
   *
   * Replaces a Bootstrap `.alert` with inline flex styles — a flat coloured bar with
   * no padding of its own, jammed against the "Email address" label. Built as DOM
   * with .text(), never an HTML string: `msg` comes off the wire and the old code
   * concatenated it straight into .html().
   */
  function renderAuthStatus(kind, title, msg, showBar) {
    var isSuccess = kind === 'success';

    var $mark = $('<span/>', { 'class': 'ipb-auth-status-mark', 'aria-hidden': 'true' })
      .append($('<i/>', { 'class': isSuccess ? 'fa-solid fa-check' : 'fa-solid fa-triangle-exclamation' }));

    var $body = $('<div/>', { 'class': 'ipb-auth-status-body' })
      .append($('<p/>', { 'class': 'ipb-auth-status-title', text: title }));

    if (msg) {
      $body.append($('<p/>', { 'class': 'ipb-auth-status-msg', text: msg }));
    }

    var $status = $('<div/>', {
      'class': 'ipb-auth-status ' + (isSuccess ? 'is-success' : 'is-error'),
      // Success is polite; a failure has to be announced.
      role: isSuccess ? 'status' : 'alert',
    }).append($mark, $body);

    if (showBar) {
      $status
        .css('--ipb-auth-redirect', LOGIN_REDIRECT_MS + 'ms')
        .append($('<span/>', { 'class': 'ipb-auth-status-bar', 'aria-hidden': 'true' }).append($('<span/>')));
    }

    $('#loginFeedback').empty().append($status);
  }

  function showLoginError(msg) {
    renderAuthStatus('error', "Couldn't sign in", msg, false);
  }

  $('#loginForm').on('submit', function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('browser_os', navigator.userAgent);
    formData.append('screen', window.screen.width + 'x' + window.screen.height);
    formData.append('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone || 'Unknown');
    formData.append('cores', navigator.hardwareConcurrency || 'Unknown');
    formData.append('ram', navigator.deviceMemory ? navigator.deviceMemory + 'GB' : 'Unknown');
    formData.append('platform', navigator.platform || 'Unknown');

    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (csrfHash && !formData.has(csrfName)) formData.append(csrfName, csrfHash);

    var $btn = $(this).find('button[type="submit"]');
    $.ajax({
      url: loginValidateUrl,
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      dataType: 'json',
      beforeSend: function (req) {
        $('#loginForm .error').html('');
        $('#loginFeedback').html('');
        $btn.html("<i class='fas fa-spinner fa-spin'></i> Please wait").prop('disabled', true);
        var h = document.querySelector('meta[name="csrf-header"]')?.getAttribute('content');
        var t = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (h && t) req.setRequestHeader(h, t);
      },
      success: function (result) {
        if (result && result.status === 'success') {
          /* The server's msg — "Login successful. Redirecting to the dashboard..." —
             states the outcome AND the next step in one run-on sentence. Split it: the
             headline is what happened, the line under it is what happens next, and the
             bar shows how long that takes. The button stays disabled and reads
             "Signing in" so the form cannot be submitted twice during the wait. */
          $btn.html("<i class='fas fa-spinner fa-spin' aria-hidden='true'></i> Signing in").prop('disabled', true);
          renderAuthStatus('success', 'Signed in', 'Taking you to your dashboard…', true);
          setTimeout(function () { location.href = result.response.redirect; }, LOGIN_REDIRECT_MS);
          return;
        }

        $btn.html('Sign in').prop('disabled', false);
        showLoginError((result && result.response) ? result.response : 'Login failed.');
      },
      error: function (xhr) {
        $btn.html('Sign in').prop('disabled', false);
        var result = null;
        try { result = xhr.responseJSON || JSON.parse(xhr.responseText); } catch (err) {}
        if (result && result.status === 'validation-error' && typeof result.response === 'object') {
          $.each(result.response, function (prefix, val) {
            $('#' + prefix + '-error').text(val);
          });
          showLoginError('Please fix the errors below.');
          return;
        }
        showLoginError((result && result.response) || 'An error occurred during login.');
      }
    });
  });

  /* Registration */
  if (!tenantOnly) {
    var districts = {
      Barishal: [{id:34,name:'Barguna'},{id:35,name:'Barishal'},{id:36,name:'Bhola'},{id:37,name:'Jhalokati'},{id:38,name:'Patuakhali'},{id:39,name:'Pirojpur'}],
      Chattogram: [{id:40,name:'Bandarban'},{id:41,name:'Brahmanbaria'},{id:42,name:'Chandpur'},{id:43,name:'Chattogram'},{id:44,name:'Cumilla'},{id:45,name:"Cox's Bazar"},{id:46,name:'Feni'},{id:47,name:'Khagrachari'},{id:48,name:'Lakshmipur'},{id:49,name:'Noakhali'},{id:50,name:'Rangamati'}],
      Dhaka: [{id:1,name:'Dhaka'},{id:2,name:'Faridpur'},{id:3,name:'Gazipur'},{id:4,name:'Gopalganj'},{id:6,name:'Kishoreganj'},{id:7,name:'Madaripur'},{id:8,name:'Manikganj'},{id:9,name:'Munshiganj'},{id:11,name:'Narayanganj'},{id:12,name:'Narsingdi'},{id:14,name:'Rajbari'},{id:15,name:'Shariatpur'},{id:17,name:'Tangail'}],
      Khulna: [{id:55,name:'Bagerhat'},{id:56,name:'Chuadanga'},{id:57,name:'Jessore'},{id:58,name:'Jhenaidah'},{id:59,name:'Khulna'},{id:60,name:'Kushtia'},{id:61,name:'Magura'},{id:62,name:'Meherpur'},{id:63,name:'Narail'},{id:64,name:'Satkhira'}],
      Rajshahi: [{id:18,name:'Bogura'},{id:19,name:'Joypurhat'},{id:20,name:'Naogaon'},{id:21,name:'Natore'},{id:22,name:'Nawabganj'},{id:23,name:'Pabna'},{id:24,name:'Rajshahi'},{id:25,name:'Sirajganj'}],
      Rangpur: [{id:26,name:'Dinajpur'},{id:27,name:'Gaibandha'},{id:28,name:'Kurigram'},{id:29,name:'Lalmonirhat'},{id:30,name:'Nilphamari'},{id:31,name:'Panchagarh'},{id:32,name:'Rangpur'},{id:33,name:'Thakurgaon'}],
      Sylhet: [{id:51,name:'Habiganj'},{id:52,name:'Moulvibazar'},{id:53,name:'Sunamganj'},{id:54,name:'Sylhet'}],
      Mymensingh: [{id:5,name:'Jamalpur'},{id:10,name:'Mymensingh'},{id:13,name:'Netrokona'},{id:16,name:'Sherpur'}]
    };

    $('#division').on('change', function () {
      var sel = $(this).val();
      var $d = $('#district').empty().append('<option value="">Select district</option>');
      if (sel && districts[sel]) {
        districts[sel].forEach(function (x) {
          $d.append('<option value="' + x.id + '">' + x.name + '</option>');
        });
      }
    });

    $('#package').on('change', function () {
      var $sel = $(this);
      var val = $sel.val();
      var $o = $sel.find('option:selected');
      var $hint = $('#package-info');
      var $text = $hint.find('.ipb-auth-package-hint-text');
      var $addons = $('#payg-addon-group');
      var $custom = $('#custom-plan-group');

      $addons.prop('hidden', val !== 'payg');
      $custom.prop('hidden', val !== 'custom');

      if (val === 'payg') {
        $hint.addClass('is-active');
        var paygTrial = parseInt($sel.data('payg-trial-days'), 10) || 14;
        $('#ipbRegTrialBadge').text(paygTrial + ' days free');
        $text.text('No customer limit · ৳' + $sel.data('payg-base-fee') + '/mo + ৳' + $sel.data('payg-rate')
          + ' per active user · ' + paygTrial + '-day free trial, then billed from your wallet (min top-up ৳'
          + $sel.data('payg-min-topup') + ')');
      } else if (val === 'custom') {
        $hint.addClass('is-active');
        $text.text('Tell us what you need — your account activates once our team sets up your tailored plan.');
      } else if (val) {
        $hint.addClass('is-active');
        var trialDays = parseInt($o.data('trial-days'), 10) || 14;
        $('#ipbRegTrialBadge').text(trialDays + ' days free');
        $text.text('Up to ' + $o.data('bandwidth') + ' customers · ৳' + $o.data('package-details') + ' / month · '
          + trialDays + '-day free trial');
      } else {
        $hint.removeClass('is-active');
        $text.text('Select a package to see customer limit and pricing');
      }
    });

    /* Preselect the plan carried over from the landing pricing CTAs.
       Accepts a package id, 'payg', or a plan name (basic/standard/premium). */
    (function () {
      var $sel = $('#package');
      var plan = new URLSearchParams(window.location.search).get('plan') || $sel.data('selected-plan');
      if (!plan) return;
      plan = String(plan);
      if ($sel.find('option[value="' + plan + '"]').length) {
        $sel.val(plan).trigger('change');
        return;
      }
      // Fallback: match by package name (landing CTAs pass tier names).
      var wanted = plan.toLowerCase();
      $sel.find('option').each(function () {
        if ($(this).text().toLowerCase().indexOf(wanted) !== -1) {
          $sel.val($(this).val()).trigger('change');
          return false;
        }
      });
    })();

    $('#registrationForm').on('submit', function (e) {
      e.preventDefault();
      $('.error-text').text('');
      var $btn = $(this).find('button[type="submit"]');
      $.ajax({
        url: '<?= route_to('route.auth.submit') ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        beforeSend: function (req) {
          var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          if (token) req.setRequestHeader('<?= csrf_header() ?>', token);
          $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating…');
        },
        success: function (response) {
          $btn.prop('disabled', false).html('Create account');
          if (response.status === 'error') {
            if (response.errors) {
              Object.keys(response.errors).forEach(function (f) {
                $('#error_' + f).text(response.errors[f]);
              });
            }
            tata.error('Check your form', 'Please fix the highlighted fields.');
          } else if (response.status === 'success') {
            tata.success('Welcome aboard', response.message || 'Account created! Taking you to sign in…');
            $('#registrationForm')[0].reset();
            $('#payg-addon-group, #custom-plan-group').prop('hidden', true);
            history.replaceState({ authMode: 'login' }, '', loginPath);
            setMode('login', false);
            showRegisteredAlert(response.pending === true);
          }
        },
        error: function () {
          $btn.prop('disabled', false).html('Create account');
          tata.error("Couldn't create your account", "Please check your details and try again.");
        }
      });
    });
  }
})();
</script>

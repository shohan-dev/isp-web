/**
 * Premium toast defaults for Tata (global).
 * Existing tata.success / tata.error / tata.info calls pick these up automatically.
 */
(function () {
  if (typeof window.tata !== 'object' || !window.tata) return;

  var defaults = {
    position: 'tr',
    animate: 'slide',
    duration: 3200,
    progress: true,
    holding: false,
    closeBtn: true
  };

  function wrap(fn) {
    return function (title, text, opts) {
      var merged = Object.assign({}, defaults, opts || {});
      return fn.call(window.tata, title, text, merged);
    };
  }

  ['text', 'log', 'info', 'warn', 'success', 'error', 'ask'].forEach(function (key) {
    if (typeof window.tata[key] === 'function') {
      window.tata[key] = wrap(window.tata[key]);
    }
  });

  // Unified helper used by pages that don't call tata directly
  window.ipbToast = function (message, type, title) {
    type = type || 'info';
    title = title || (type === 'success' ? 'Success' : type === 'error' ? 'Error' : type === 'warn' ? 'Warning' : 'Notice');
    var map = {
      success: 'success',
      error: 'error',
      warn: 'warn',
      warning: 'warn',
      info: 'info',
      log: 'log'
    };
    var method = map[type] || 'info';
    if (typeof window.tata[method] === 'function') {
      window.tata[method](title, message);
      return;
    }
    // Fallback minimal toast
    var el = document.createElement('div');
    el.className = 'tata ' + method + ' top-right fade-in';
    el.innerHTML = '<i class="tata-icon material-icons">info</i><div class="tata-body"><h4 class="tata-title">' +
      title + '</h4><p class="tata-text">' + message + '</p></div>';
    document.body.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  };
})();

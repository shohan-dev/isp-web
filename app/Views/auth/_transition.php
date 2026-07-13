<script>
(function () {
  var loginPath = <?= json_encode(parse_url(route_to('route.auth.login'), PHP_URL_PATH) ?: '/auth/login'); ?>;
  var registerPath = <?= json_encode(parse_url(route_to('route.auth.registration'), PHP_URL_PATH) ?: '/auth/registration'); ?>;

  function isAuthSwitch(href) {
    if (!href) return false;
    try {
      var path = new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
      return path === loginPath.replace(/\/+$/, '') || path === registerPath.replace(/\/+$/, '');
    } catch (e) {
      return false;
    }
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[href]');
    if (!link || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || link.target === '_blank') {
      return;
    }
    if (!isAuthSwitch(link.href)) return;

    e.preventDefault();
    document.body.classList.add('ipb-auth-leaving');
    window.setTimeout(function () {
      window.location.href = link.href;
    }, 300);
  });
})();
</script>

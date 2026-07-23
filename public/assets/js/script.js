// Make sure jQuery has been loaded
if (typeof jQuery === "undefined") {
  throw new Error("AdminLTE requires jQuery");
}

/* ControlSidebar()
 * ===============
 * Toggles the state of the control sidebar
 *
 * @Usage: $('#control-sidebar-trigger').controlSidebar(options)
 *         or add [data-toggle="control-sidebar"] to the trigger
 *         Pass any option as data-option="value"
 */
+(function ($) {
  "use strict";

  var DataKey = "lte.controlsidebar";

  var Default = {
    controlsidebarSlide: true,
  };

  var Selector = {
    sidebar: ".control-sidebar",
    data: '[data-toggle="control-sidebar"]',
    open: ".control-sidebar-open",
    bg: ".control-sidebar-bg",
    wrapper: ".wrapper",
    content: ".content-wrapper",
    boxed: ".layout-boxed",
  };

  var ClassName = {
    open: "control-sidebar-open",
    transition: "control-sidebar-hold-transition",
    fixed: "fixed",
  };

  var Event = {
    collapsed: "collapsed.controlsidebar",
    expanded: "expanded.controlsidebar",
  };

  // ControlSidebar Class Definition
  // ===============================
  var ControlSidebar = function (element, options) {
    this.element = element;
    this.options = options;
    this.hasBindedResize = false;

    this.init();
  };

  ControlSidebar.prototype.init = function () {
    // Add click listener if the element hasn't been
    // initialized using the data API
    if (!$(this.element).is(Selector.data)) {
      $(this).on("click", this.toggle);
    }

    this.fix();
    $(window).resize(
      function () {
        this.fix();
      }.bind(this)
    );
  };

  ControlSidebar.prototype.toggle = function (event) {
    if (event) event.preventDefault();

    this.fix();

    if (
      !$(Selector.sidebar).is(Selector.open) &&
      !$("body").is(Selector.open)
    ) {
      this.expand();
    } else {
      this.collapse();
    }
  };

  ControlSidebar.prototype.expand = function () {
    $(Selector.sidebar).show();
    if (!this.options.controlsidebarSlide) {
      $("body")
        .addClass(ClassName.transition)
        .addClass(ClassName.open)
        .delay(50)
        .queue(function () {
          $("body").removeClass(ClassName.transition);
          $(this).dequeue();
        });
    } else {
      $(Selector.sidebar).addClass(ClassName.open);
    }

    $(this.element).trigger($.Event(Event.expanded));
  };

  ControlSidebar.prototype.collapse = function () {
    if (!this.options.controlsidebarSlide) {
      $("body")
        .addClass(ClassName.transition)
        .removeClass(ClassName.open)
        .delay(50)
        .queue(function () {
          $("body").removeClass(ClassName.transition);
          $(this).dequeue();
        });
    } else {
      $(Selector.sidebar).removeClass(ClassName.open);
    }
    $(Selector.sidebar).fadeOut();
    $(this.element).trigger($.Event(Event.collapsed));
  };

  ControlSidebar.prototype.fix = function () {
    if ($("body").is(Selector.boxed)) {
      this._fixForBoxed($(Selector.bg));
    }
  };

  // Private

  ControlSidebar.prototype._fixForBoxed = function (bg) {
    bg.css({
      position: "absolute",
      height: $(Selector.wrapper).height(),
    });
  };

  // Plugin Definition
  // =================
  function Plugin(option) {
    return this.each(function () {
      var $this = $(this);
      var data = $this.data(DataKey);

      if (!data) {
        var options = $.extend(
          {},
          Default,
          $this.data(),
          typeof option == "object" && option
        );
        $this.data(DataKey, (data = new ControlSidebar($this, options)));
      }

      if (typeof option == "string") data.toggle();
    });
  }

  var old = $.fn.controlSidebar;

  $.fn.controlSidebar = Plugin;
  $.fn.controlSidebar.Constructor = ControlSidebar;

  // No Conflict Mode
  // ================
  $.fn.controlSidebar.noConflict = function () {
    $.fn.controlSidebar = old;
    return this;
  };

  // ControlSidebar Data API
  // =======================
  $(document).on("click", Selector.data, function (event) {
    if (event) event.preventDefault();
    Plugin.call($(this), "toggle");
  });
})(jQuery);

/* PushMenu()
 * ==========
 * Adds the push menu functionality to the sidebar.
 *
 * @usage: $('.btn').pushMenu(options)
 *          or add [data-toggle="push-menu"] to any button
 *          Pass any option as data-option="value"
 */
+(function ($) {
  "use strict";

  var DataKey = "lte.pushmenu";

  var Default = {
    collapseScreenSize: 767,
    expandOnHover: false,
    expandTransitionDelay: 200,
  };

  var Selector = {
    collapsed: ".sidebar-collapse",
    open: ".sidebar-open",
    mainSidebar: ".main-sidebar",
    contentWrapper: ".content-wrapper",
    searchInput: ".sidebar-form .form-control",
    button: '[data-toggle="push-menu"]',
    mini: ".sidebar-mini",
    expanded: ".sidebar-expanded-on-hover",
    layoutFixed: ".fixed",
  };

  var ClassName = {
    collapsed: "sidebar-collapse",
    open: "sidebar-open",
    mini: "sidebar-mini",
    expanded: "sidebar-expanded-on-hover",
    expandFeature: "sidebar-mini-expand-feature",
    layoutFixed: "fixed",
  };

  var Event = {
    expanded: "expanded.pushMenu",
    collapsed: "collapsed.pushMenu",
  };

  // PushMenu Class Definition
  // =========================
  var PushMenu = function (options) {
    this.options = options;
    this.init();
  };

  PushMenu.prototype.init = function () {
    if (
      this.options.expandOnHover ||
      $("body").is(Selector.mini + Selector.layoutFixed)
    ) {
      this.expandOnHover();
      $("body").addClass(ClassName.expandFeature);
    }

    $(Selector.contentWrapper).click(
      function () {
        // Enable hide menu when clicking on the content-wrapper on small screens
        if (
          $(window).width() <= this.options.collapseScreenSize &&
          $("body").hasClass(ClassName.open)
        ) {
          this.close();
        }
      }.bind(this)
    );

    // __Fix for android devices
    $(Selector.searchInput).click(function (e) {
      e.stopPropagation();
    });
  };

  PushMenu.prototype.toggle = function () {
    var windowWidth = $(window).width();
    var isOpen = !$("body").hasClass(ClassName.collapsed);

    if (windowWidth <= this.options.collapseScreenSize) {
      isOpen = $("body").hasClass(ClassName.open);
    }

    if (!isOpen) {
      this.open();
    } else {
      this.close();
    }
  };

  PushMenu.prototype.open = function () {
    var windowWidth = $(window).width();

    if (windowWidth > this.options.collapseScreenSize) {
      $("body")
        .removeClass(ClassName.collapsed)
        .trigger($.Event(Event.expanded));
    } else {
      $("body").addClass(ClassName.open).trigger($.Event(Event.expanded));
    }
  };

  PushMenu.prototype.close = function () {
    var windowWidth = $(window).width();
    if (windowWidth > this.options.collapseScreenSize) {
      $("body").addClass(ClassName.collapsed).trigger($.Event(Event.collapsed));
    } else {
      $("body")
        .removeClass(ClassName.open + " " + ClassName.collapsed)
        .trigger($.Event(Event.collapsed));
    }
  };

  PushMenu.prototype.expandOnHover = function () {
    $(Selector.mainSidebar).hover(
      function () {
        if (
          $("body").is(Selector.mini + Selector.collapsed) &&
          $(window).width() > this.options.collapseScreenSize
        ) {
          this.expand();
        }
      }.bind(this),
      function () {
        if ($("body").is(Selector.expanded)) {
          this.collapse();
        }
      }.bind(this)
    );
  };

  PushMenu.prototype.expand = function () {
    setTimeout(function () {
      $("body").removeClass(ClassName.collapsed).addClass(ClassName.expanded);
    }, this.options.expandTransitionDelay);
  };

  PushMenu.prototype.collapse = function () {
    setTimeout(function () {
      $("body").removeClass(ClassName.expanded).addClass(ClassName.collapsed);
    }, this.options.expandTransitionDelay);
  };

  // PushMenu Plugin Definition
  // ==========================
  function Plugin(option) {
    return this.each(function () {
      var $this = $(this);
      var data = $this.data(DataKey);

      if (!data) {
        var options = $.extend(
          {},
          Default,
          $this.data(),
          typeof option == "object" && option
        );
        $this.data(DataKey, (data = new PushMenu(options)));
      }

      if (option === "toggle") data.toggle();
    });
  }

  var old = $.fn.pushMenu;

  $.fn.pushMenu = Plugin;
  $.fn.pushMenu.Constructor = PushMenu;

  // No Conflict Mode
  // ================
  $.fn.pushMenu.noConflict = function () {
    $.fn.pushMenu = old;
    return this;
  };

  // Data API
  // ========
  $(document).on("click", Selector.button, function (e) {
    e.preventDefault();
    Plugin.call($(this), "toggle");
  });
  $(window).on("load", function () {
    Plugin.call($(Selector.button));
  });
})(jQuery);

/* Layout()
 * ========
 * Implements the dashboard layout.
 * Fixes the layout height in case min-height fails.
 *
 * @usage activated automatically upon window load.
 *        Configure any options by passing data-option="value"
 *        to the body tag.
 */
+(function ($) {
  ("use strict");

  var DataKey = "lte.layout";

  var Default = {
    slimscroll: true,
    resetHeight: true,
  };

  var Selector = {
    wrapper: ".wrapper",
    contentWrapper: ".content-wrapper",
    layoutBoxed: ".layout-boxed",
    mainFooter: ".main-footer",
    mainHeader: ".main-header",
    mainSidebar: ".main-sidebar",
    slimScrollDiv: "slimScrollDiv",
    sidebar: ".sidebar",
    controlSidebar: ".control-sidebar",
    fixed: ".fixed",
    sidebarMenu: ".sidebar-menu",
    logo: ".main-header .logo",
  };

  var ClassName = {
    fixed: "fixed",
    holdTransition: "hold-transition",
  };

  var Layout = function (options) {
    this.options = options;
    this.bindedResize = false;
    this.activate();
  };

  Layout.prototype.activate = function () {
    this.fix();
    this.fixSidebar();

    $("body").removeClass(ClassName.holdTransition);

    if (this.options.resetHeight) {
      $("body, html, " + Selector.wrapper).css({
        height: "auto",
        "min-height": "100%",
      });
    }

    if (!this.bindedResize) {
      $(window).resize(
        function () {
          this.fix();
          this.fixSidebar();

          $(Selector.logo + ", " + Selector.sidebar).one(
            "webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend",
            function () {
              this.fix();
              this.fixSidebar();
            }.bind(this)
          );
        }.bind(this)
      );

      this.bindedResize = true;
    }

    $(Selector.sidebarMenu).on(
      "expanded.tree",
      function () {
        this.fix();
        this.fixSidebar();
      }.bind(this)
    );

    $(Selector.sidebarMenu).on(
      "collapsed.tree",
      function () {
        this.fix();
        this.fixSidebar();
      }.bind(this)
    );
  };

  Layout.prototype.fix = function () {
    // Remove overflow from .wrapper if layout-boxed exists
    $(Selector.layoutBoxed + " > " + Selector.wrapper).css(
      "overflow",
      "hidden"
    );

    // Get window height and the wrapper height
    var footerHeight = $(Selector.mainFooter).outerHeight() || 0;
    var headerHeight = $(Selector.mainHeader).outerHeight() || 0;
    var neg = headerHeight + footerHeight;
    var windowHeight = $(window).height();
    var sidebarHeight = $(Selector.sidebar).outerHeight() || 0;

    // Set the min-height of the content and sidebar based on
    // the height of the document.
    if ($("body").hasClass(ClassName.fixed)) {
      $(Selector.contentWrapper).css("min-height", windowHeight - footerHeight);
    } else {
      var postSetHeight;

      if (windowHeight >= sidebarHeight + headerHeight) {
        $(Selector.contentWrapper).css("min-height", windowHeight - neg);
        postSetHeight = windowHeight - neg;
      } else {
        $(Selector.contentWrapper).css("min-height", sidebarHeight);
        postSetHeight = sidebarHeight;
      }

      // Fix for the control sidebar height
      var $controlSidebar = $(Selector.controlSidebar);
      if (typeof $controlSidebar !== "undefined") {
        if ($controlSidebar.height() > postSetHeight)
          $(Selector.contentWrapper).css(
            "min-height",
            $controlSidebar.height()
          );
      }
    }
  };

  Layout.prototype.fixSidebar = function () {
    // Make sure the body tag has the .fixed class
    if (!$("body").hasClass(ClassName.fixed)) {
      if (typeof $.fn.slimScroll !== "undefined") {
        $(Selector.sidebar).slimScroll({ destroy: true }).height("auto");
      }
      return;
    }

    // Enable slimscroll for fixed layout
    if (this.options.slimscroll) {
      if (typeof $.fn.slimScroll !== "undefined") {
        // Destroy if it exists
        // $(Selector.sidebar).slimScroll({ destroy: true }).height('auto')

        // Add slimscroll
        if ($(Selector.mainSidebar).find(Selector.slimScrollDiv).length === 0) {
          $(Selector.sidebar).slimScroll({
            height: $(window).height() - $(Selector.mainHeader).height() + "px",
          });
        }
      }
    }
  };

  // Plugin Definition
  // =================
  function Plugin(option) {
    return this.each(function () {
      var $this = $(this);
      var data = $this.data(DataKey);

      if (!data) {
        var options = $.extend(
          {},
          Default,
          $this.data(),
          typeof option === "object" && option
        );
        $this.data(DataKey, (data = new Layout(options)));
      }

      if (typeof option === "string") {
        if (typeof data[option] === "undefined") {
          throw new Error("No method named " + option);
        }
        data[option]();
      }
    });
  }

  var old = $.fn.layout;

  $.fn.layout = Plugin;
  $.fn.layout.Constuctor = Layout;

  // No conflict mode
  // ================
  $.fn.layout.noConflict = function () {
    $.fn.layout = old;
    return this;
  };

  // Layout DATA-API
  // ===============
  $(window).on("load", function () {
    Plugin.call($("body"));
  });

  // Bootstrap Tooltip & Select2
  // ===============
  $(document).ready(function () {

    //init bootstrap tooltips
    var $tooltips = $('[data-toggle="tooltip"]');
    if ($tooltips.length) $tooltips.tooltip();

    // init select2 — only pages that actually have a select.form-control pay
    // the init cost (docs/production-optimization/PAGE-LOAD-PERFORMANCE-AUDIT-PART-2.md A4)
    var $selects = $('select[class="form-control"]');
    if ($selects.length) $selects.select2({width: '100%',});
  });
})(jQuery);

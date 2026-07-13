var tata = function(n) {
    function t(e) {
        if (r[e]) return r[e].exports;
        var o = r[e] = {
            i: e,
            l: !1,
            exports: {}
        };
        return n[e].call(o.exports, o, o.exports, t), o.l = !0, o.exports
    }
    var r = {};
    return t.m = n, t.c = r, t.d = function(n, r, e) {
        t.o(n, r) || Object.defineProperty(n, r, {
            configurable: !1,
            enumerable: !0,
            get: e
        })
    }, t.n = function(n) {
        var r = n && n.__esModule ? function() {
            return n.default
        } : function() {
            return n
        };
        return t.d(r, "a", r), r
    }, t.o = function(n, t) {
        return Object.prototype.hasOwnProperty.call(n, t)
    }, t.p = "", t(t.s = 0)
}([function(n, t, r) {
    n.exports = r(1)
}, function(n, t, r) {
    "use strict";

    function e() {
        var n = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "fade",
            t = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : "tr";
        if ("slide" === n) switch (t) {
            case "tr":
            case "mr":
            case "br":
                return "slide-right-out";
            case "tl":
            case "ml":
            case "bl":
                return "slide-left-out";
            case "tm":
                return "slide-top-out";
            case "bm":
                return "slide-bottom-out"
        }
        return "fade-out"
    }

    function o(n) {
        var t = setTimeout(function() {
            "function" == typeof n.remove ? n.remove() : document.body.removeChild(n), clearTimeout(t)
        }, 800)
    }

    function i(n, t, r) {
        var i = "tata-" + Date.now(),
            a = function() {
                switch (arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "text") {
                    case "text":
                        return "chat_bubble";
                    case "log":
                        return "textsms";
                    case "info":
                        return "forum";
                    case "warn":
                        return "info_outline";
                    case "success":
                        return "check";
                    case "error":
                        return "block";
                    case "ask":
                        return "help_outline";
                    default:
                        return ""
                }
            }(r.type),
            s = function() {
                switch (arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "tr") {
                    case "tr":
                    default:
                        return "top-right";
                    case "tm":
                        return "top-mid";
                    case "tl":
                        return "top-left";
                    case "mr":
                        return "mid-right";
                    case "mm":
                        return "mid-mid";
                    case "ml":
                        return "mid-left";
                    case "br":
                        return "bottom-right";
                    case "bm":
                        return "bottom-mid";
                    case "bl":
                        return "bottom-left"
                }
            }(r.position),
            f = function() {
                var n = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "fade",
                    t = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : "tr";
                if ("slide" === n) switch (t) {
                    case "tr":
                    case "mr":
                    case "br":
                        return "slide-right-in";
                    case "tl":
                    case "ml":
                    case "bl":
                        return "slide-left-in";
                    case "tm":
                        return "slide-top-in";
                    case "bm":
                        return "slide-bottom-in"
                }
                return "fade-in"
            }(r.animate, r.position),
            c = {
                title: n,
                text: t,
                opts: r,
                id: i
            },
            u = l.findIndex(function(n) {
                return n.id === i
            }),
            p = 0 === u ? null : l[u - 1];
        l.push(c);
        var d = '\n  <div class="tata ' + r.type + " " + f + " " + s + '" id=' + i + '>\n    <i class="tata-icon material-icons">' + a + '</i>\n    <div class="tata-body">\n      <h4 class="tata-title">' + n + '</h4>\n      <p class="tata-text">' + t + "</p>\n    </div>\n    " + (r.closeBtn || r.holding ? '<button class="tata-close material-icons">clear</button>' : "") + "\n    " + (!r.holding && r.progress ? '<div class="tata-progress"></div>' : "") + "\n  </div>\n ";
        document.body.insertAdjacentHTML("beforeend", d), p && p.opts.position === c.opts.position && o(document.getElementById(p.id));
        var $ = document.getElementById(i);
        if (r.onClick && "function" == typeof r.onClick && $.addEventListener("click", (function(n) {
                n.target.classList.contains("tata-close") || this.opts.onClick.call(this)
            }).bind(c), {
                capture: !0,
                once: !0
            }), !r.holding && r.progress) {
            $.querySelector(".tata-progress").style.animation = r.duration / 1e3 + "s reduceWidth linear forwards";
            var h = setTimeout(function() {
                var n = l.findIndex(function(n) {
                    return n == n
                });
                l.splice(n, 1), $.classList.add(e(c.opts.animate, c.opts.position)), o($), clearTimeout(h), c.opts.onClose && "function" == typeof c.opts.onClose && c.opts.onClose.call(c)
            }, r.duration)
        }
    }

    function a() {
        var n = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "Hello",
            t = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : "Today is " + (new Date).toLocaleString(),
            r = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
        i(n, t, Object.assign({}, f, r, {
            type: "text"
        }))
    }
    Object.defineProperty(t, "__esModule", {
        value: !0
    }), t.text = a, t.log = function() {
        var n = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "Hello",
            t = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : "Today is " + (new Date).toLocaleString(),
            r = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
        i(n, t, Object.assign({}, f, r, {
            type: "log"
        }))
    }, t.info = function() {
        var n = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "Hello",
            t = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : "Today is " + (new Date).toLocaleString(),
            r = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
        i(n, t, Object.assign({}, f, r, {
            type: "info"
        }))
    }, t.warn = function() {
        var n = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "Hello",
            t = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : "Today is " + (new Date).toLocaleString(),
            r = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
        i(n, t, Object.assign({}, f, r, {
            type: "warn"
        }))
    }, t.error = function() {
        var n = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "Hello",
            t = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : "Today is " + (new Date).toLocaleString(),
            r = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
        i(n, t, Object.assign({}, f, r, {
            type: "error"
        }))
    }, t.success = function() {
        var n = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : "Hello",
            t = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : "Today is " + (new Date).toLocaleString(),
            r = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
        i(n, t, Object.assign({}, f, r, {
            type: "success"
        }))
    }, t.ask = function() {
        var n = Object.assign({}, f, opts, {
            type: "ask"
        });
        i(title, a, n)
    }, t.clear = function() {
        l.forEach(function(n) {
            return o(document.getElementById(n.id))
        }), l.length = 0
    }, r(2), (s = document.createElement("link")).rel = "stylesheet", s.href = "https://fonts.googleapis.com/icon?family=Material+Icons", document.head.appendChild(s), document.addEventListener("click", function(n) {
        var t = n.target;
        if (t.classList.contains("tata-close")) {
            var r = t.parentNode.getAttribute("id"),
                i = l.find(function(n) {
                    return n.id === r
                }),
                a = document.getElementById(r);
            a.classList.add(e(i.opts.animate, i.opts.position)), o(a), i.opts.onClose && "function" == typeof i.opts.onClose && i.opts.onClose.call(i)
        }
    }, !1);
    var s, l = [],
        f = {
            type: "log",
            position: "tr",
            animate: "fade",
            duration: 2e3,
            progress: !0,
            holding: !1,
            closeBtn: !0,
            onClick: null,
            onClose: null
        }
}, function(n, t, r) {
    var e = r(3);
    "string" == typeof e && (e = [
        [n.i, e, ""]
    ]), r(5)(e, {
        hmr: !0,
        transform: void 0
    }), e.locals && (n.exports = e.locals)
}, function(n, t, r) {
    (n.exports = r(4)(void 0)).push([n.i, ".tata {\r\n  position: fixed;\r\n  display: flex;\r\n  justify-content: space-around;\r\n  align-items: center;\r\n  width: 300px;\r\n  border-radius: 3px;\r\n  color: #ffffff;\r\n  font-size: 19px !important;\r\n font-weight: bold !important;\r\n  z-index: 9999;\r\n  pointer-events: auto;\r\n  padding: 12px 14px 12px 20px;\r\n  box-shadow: 0 24px 38px 3px rgba(0, 0, 0, 0.14), 0 9px 46px 8px rgba(0, 0, 0, 0.12), 0 11px 15px -7px rgba(0, 0, 0, 0.2);\r\n}\r\n\r\n.tata:hover {\r\n  opacity: 1;\r\n}\r\n\r\n.tata * {\r\n  box-sizing: border-box;\r\n}\r\n\r\n.tata .tata-icon {\r\n  font-size: 2em;\r\n  color: inherit;\r\n}\r\n\r\n.tata .tata-body {\r\n  margin: 0;\r\n  padding: 0 14px;\r\n  min-height: 38px;\r\n  min-width: 260px;\r\n}\r\n\r\n.tata .tata-title {\r\n  margin: 0 0 2px 0;\r\n color: #fff !important;\r\n  font-size: 0.85em;\r\n}\r\n\r\n.tata .tata-text {\r\n  margin: 0;\r\n  font-size: .75em;\r\n}\r\n\r\n.tata .tata-close {\r\n  position: absolute;\r\n  top: 6px;\r\n  right: 6px;\r\n  border: none;\r\n  margin: 0;\r\n  padding: 0;\r\n  font-size: 1em;\r\n  font-weight: bold;\r\n  color: inherit;\r\n  cursor: pointer;\r\n  outline: none;\r\n  background: transparent;\r\n}\r\n\r\n.tata-progress {\r\n  position: absolute;\r\n  bottom: -1px;\r\n  left: 0;\r\n  width: 100%;\r\n  height: 5px;\r\n  border-radius: 0 0 3px 3px;\r\n  background: rgba(0, 0, 0, 0.2);\r\n}\r\n\r\n.tata .tata-close:hover {\r\n  opacity: 0.4;\r\n}\r\n\r\n.tata.top-right {\r\n  top: 12px;\r\n  right: 12px;\r\n}\r\n\r\n.tata.top-mid {\r\n  top: 12px;\r\n  left: 50%;\r\n  transform: translateX(-50%);\r\n}\r\n\r\n.tata.top-left {\r\n  top: 12px;\r\n  left: 12px;\r\n}\r\n\r\n.tata.bottom-right {\r\n  right: 12px;\r\n  bottom: 18px;\r\n}\r\n\r\n.tata.bottom-mid {\r\n  left: 50%;\r\n  bottom: 18px;\r\n  transform: translateX(-50%);\r\n}\r\n\r\n.tata.bottom-left {\r\n  bottom: 18px;\r\n  left: 12px;\r\n}\r\n\r\n.tata.mid-right {\r\n  top: 50%;\r\n  right: 12px;\r\n  transform: translateY(-50%);\r\n}\r\n\r\n.tata.mid-left {\r\n  top: 50%;\r\n  left: 12px;\r\n  transform: translateY(-50%);\r\n}\r\n\r\n.tata.mid-mid {\r\n  top: 35%;\r\n  left: 50%;\r\n  transform: translate(-50%, -50%);\r\n}\r\n\r\n.tata.text {\r\n  color: #fff;\r\n  background: #323232;\r\n}\r\n\r\n.tata.log {\r\n  color: #333333;\r\n  background: #fffffc;\r\n}\r\n\r\n.tata.info {\r\n  background: #2ca9e1;\r\n}\r\n\r\n.tata.warn {\r\n  background: #f89406;\r\n}\r\n\r\n.tata.error {\r\n  background: #e9546b;\r\n}\r\n\r\n.tata.success {\r\n  background: #00A65A;\r\n}\r\n\r\n.tata.fade-in {\r\n  animation: .4s ease-in fadeIn forwards;\r\n}\r\n\r\n.tata.fade-out {\r\n  animation: .4s linear fadeOut forwards;\r\n}\r\n\r\n.tata.slide-right-in {\r\n  animation: .4s ease slideRightIn forwards;\r\n}\r\n\r\n.tata.slide-right-out {\r\n  animation: .4s ease slideRightOut forwards;\r\n}\r\n\r\n.tata.slide-left-in {\r\n  animation: .4s ease slideLeftIn forwards;\r\n}\r\n\r\n.tata.slide-left-out {\r\n  animation: .4s ease slideLeftOut forwards;\r\n}\r\n\r\n.tata.slide-top-in {\r\n  animation: .4s ease slideTopIn forwards;\r\n}\r\n\r\n.tata.slide-top-out {\r\n  animation: .4s ease slideTopOut forwards;\r\n}\r\n\r\n.tata.slide-bottom-in {\r\n  animation: .4s ease slideBottomIn forwards;\r\n}\r\n\r\n.tata.slide-bottom-out {\r\n  animation: .4s ease slideBottomOut forwards;\r\n}\r\n\r\n@keyframes fadeIn {\r\n  from {\r\n    opacity: 0;\r\n  }\r\n\r\n  to {\r\n    opacity: 1;\r\n  }\r\n}\r\n\r\n@keyframes fadeOut {\r\n  from {\r\n    opacity: 1;\r\n  }\r\n\r\n  to {\r\n    opacity: 0;\r\n  }\r\n}\r\n\r\n@keyframes slideRightIn {\r\n  from {\r\n    right: -310px;\r\n  }\r\n\r\n  to {\r\n    right: 12px;\r\n  }\r\n}\r\n\r\n@keyframes slideRightOut {\r\n  from {\r\n    right: 12px;\r\n  }\r\n\r\n  to {\r\n    right: -310px;\r\n  }\r\n}\r\n\r\n@keyframes slideLeftIn {\r\n  from {\r\n    left: -310px;\r\n  }\r\n\r\n  to {\r\n    left: 12px;\r\n  }\r\n}\r\n\r\n@keyframes slideLeftOut {\r\n  from {\r\n    left: 12px;\r\n  }\r\n\r\n  to {\r\n    left: -310px;\r\n  }\r\n}\r\n\r\n@keyframes slideTopIn {\r\n  from {\r\n    top: calc(-100% + -12px);\r\n  }\r\n  to {\r\n    top: 12px;\r\n  }\r\n}\r\n\r\n@keyframes slideTopOut {\r\n  from {\r\n    top: 12px;\r\n  }\r\n  to {\r\n    top: calc(-100% + -12px);\r\n  }\r\n}\r\n\r\n@keyframes slideBottomIn {\r\n  from {\r\n    bottom: calc(-100% + -18px);\r\n  }\r\n  to {\r\n    bottom: 18px;\r\n  }\r\n}\r\n\r\n@keyframes slideBottomOut {\r\n  from {\r\n    bottom: 18px;\r\n  }\r\n  to {\r\n    bottom: calc(-100% + -18px);\r\n  }\r\n}\r\n\r\n@keyframes reduceWidth {\r\n  from {\r\n    width: 100%;\r\n  }\r\n\r\n  to {\r\n    width: 0%;\r\n  }\r\n}", ""])
}, function(n, t) {
    n.exports = function(n) {
        var t = [];
        return t.toString = function() {
            return this.map(function(t) {
                var r = function n(t, r) {
                    var e = t[1] || "",
                        o = t[3];
                    if (!o) return e;
                    if (r && "function" == typeof btoa) {
                        var i, a = (i = o, "/*# sourceMappingURL=data:application/json;charset=utf-8;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(i)))) + " */");
                        return [e].concat(o.sources.map(function(n) {
                            return "/*# sourceURL=" + o.sourceRoot + n + " */"
                        })).concat([a]).join("\n")
                    }
                    return [e].join("\n")
                }(t, n);
                return t[2] ? "@media " + t[2] + "{" + r + "}" : r
            }).join("")
        }, t.i = function(n, r) {
            "string" == typeof n && (n = [
                [null, n, ""]
            ]);
            for (var e = {}, o = 0; o < this.length; o++) {
                var i = this[o][0];
                "number" == typeof i && (e[i] = !0)
            }
            for (o = 0; o < n.length; o++) {
                var a = n[o];
                "number" == typeof a[0] && e[a[0]] || (r && !a[2] ? a[2] = r : r && (a[2] = "(" + a[2] + ") and (" + r + ")"), t.push(a))
            }
        }, t
    }
}, function(n, t, r) {
    function e(n, t) {
        for (var r = 0; r < n.length; r++) {
            var e = n[r],
                o = $[e.id];
            if (o) {
                o.refs++;
                for (var i = 0; i < o.parts.length; i++) o.parts[i](e.parts[i]);
                for (; i < e.parts.length; i++) o.parts.push(f(e.parts[i], t))
            } else {
                var a = [];
                for (i = 0; i < e.parts.length; i++) a.push(f(e.parts[i], t));
                $[e.id] = {
                    id: e.id,
                    refs: 1,
                    parts: a
                }
            }
        }
    }

    function o(n, t) {
        for (var r = [], e = {}, o = 0; o < n.length; o++) {
            var i = n[o],
                a = t.base ? i[0] + t.base : i[0],
                s = {
                    css: i[1],
                    media: i[2],
                    sourceMap: i[3]
                };
            e[a] ? e[a].parts.push(s) : r.push(e[a] = {
                id: a,
                parts: [s]
            })
        }
        return r
    }

    function i(n, t) {
        var r = h(n.insertInto);
        if (!r) throw Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");
        var e = v[v.length - 1];
        if ("top" === n.insertAt) e ? e.nextSibling ? r.insertBefore(t, e.nextSibling) : r.appendChild(t) : r.insertBefore(t, r.firstChild), v.push(t);
        else if ("bottom" === n.insertAt) r.appendChild(t);
        else {
            if ("object" != typeof n.insertAt || !n.insertAt.before) throw Error("[Style Loader]\n\n Invalid value for parameter 'insertAt' ('options.insertAt') found.\n Must be 'top', 'bottom', or Object.\n (https://github.com/webpack-contrib/style-loader#insertat)\n");
            var o = h(n.insertInto + " " + n.insertAt.before);
            r.insertBefore(t, o)
        }
    }

    function a(n) {
        if (null === n.parentNode) return !1;
        n.parentNode.removeChild(n);
        var t = v.indexOf(n);
        t >= 0 && v.splice(t, 1)
    }

    function s(n) {
        var t = document.createElement("style");
        return n.attrs.type = "text/css", l(t, n.attrs), i(n, t), t
    }

    function l(n, t) {
        Object.keys(t).forEach(function(r) {
            n.setAttribute(r, t[r])
        })
    }

    function f(n, t) {
        if (t.transform && n.css) {
            if (!(f = t.transform(n.css))) return function() {};
            n.css = f
        }
        if (t.singleton) {
            var r, e, o, f, u, p, d = g++;
            r = m || (m = s(t)), e = c.bind(null, r, d, !1), o = c.bind(null, r, d, !0)
        } else n.sourceMap && "function" == typeof URL && "function" == typeof URL.createObjectURL && "function" == typeof URL.revokeObjectURL && "function" == typeof Blob && "function" == typeof btoa ? (e = (function(n, t, r) {
            var e = r.css,
                o = r.sourceMap,
                i = void 0 === t.convertToAbsoluteUrls && o;
            (t.convertToAbsoluteUrls || i) && (e = b(e)), o && (e += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(o)))) + " */");
            var a = new Blob([e], {
                    type: "text/css"
                }),
                s = n.href;
            n.href = URL.createObjectURL(a), s && URL.revokeObjectURL(s)
        }).bind(null, r = (u = t, p = document.createElement("link"), u.attrs.type = "text/css", u.attrs.rel = "stylesheet", l(p, u.attrs), i(u, p), p), t), o = function() {
            a(r), r.href && URL.revokeObjectURL(r.href)
        }) : (e = (function(n, t) {
            var r = t.css,
                e = t.media;
            if (e && n.setAttribute("media", e), n.styleSheet) n.styleSheet.cssText = r;
            else {
                for (; n.firstChild;) n.removeChild(n.firstChild);
                n.appendChild(document.createTextNode(r))
            }
        }).bind(null, r = s(t)), o = function() {
            a(r)
        });
        return e(n),
            function(t) {
                t ? (t.css !== n.css || t.media !== n.media || t.sourceMap !== n.sourceMap) && e(n = t) : o()
            }
    }

    function c(n, t, r, e) {
        var o = r ? "" : e.css;
        if (n.styleSheet) n.styleSheet.cssText = x(t, o);
        else {
            var i = document.createTextNode(o),
                a = n.childNodes;
            a[t] && n.removeChild(a[t]), a.length ? n.insertBefore(i, a[t]) : n.appendChild(i)
        }
    }
    var u, p, d, $ = {},
        h = (p = {}, function(n) {
            if (void 0 === p[n]) {
                var t = (function(n) {
                    return document.querySelector(n)
                }).call(this, n);
                if (t instanceof window.HTMLIFrameElement) try {
                    t = t.contentDocument.head
                } catch (r) {
                    t = null
                }
                p[n] = t
            }
            return p[n]
        }),
        m = null,
        g = 0,
        v = [],
        b = r(6);
    n.exports = function(n, t) {
        if ("undefined" != typeof DEBUG && DEBUG && "object" != typeof document) throw Error("The style-loader cannot be used in a non-browser environment");
        (t = t || {}).attrs = "object" == typeof t.attrs ? t.attrs : {}, t.singleton || (t.singleton = function() {
            return void 0 === u && (u = (function() {
                return window && document && document.all && !window.atob
            }).apply(this, arguments)), u
        }()), t.insertInto || (t.insertInto = "head"), t.insertAt || (t.insertAt = "bottom");
        var r = o(n, t);
        return e(r, t),
            function(n) {
                for (var i, a = [], s = 0; s < r.length; s++) {
                    var l = r[s];
                    (i = $[l.id]).refs--, a.push(i)
                }
                for (n && e(o(n, t), t), s = 0; s < a.length; s++)
                    if (0 === (i = a[s]).refs) {
                        for (var f = 0; f < i.parts.length; f++) i.parts[f]();
                        delete $[i.id]
                    }
            }
    };
    var x = (d = [], function(n, t) {
        return d[n] = t, d.filter(Boolean).join("\n")
    })
}, function(n, t) {
    n.exports = function(n) {
        var t = "undefined" != typeof window && window.location;
        if (!t) throw Error("fixUrls requires window.location");
        if (!n || "string" != typeof n) return n;
        var r = t.protocol + "//" + t.host,
            e = r + t.pathname.replace(/\/[^\/]*$/, "/");
        return n.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi, function(n, t) {
            var o, i = t.trim().replace(/^"(.*)"$/, function(n, t) {
                return t
            }).replace(/^'(.*)'$/, function(n, t) {
                return t
            });
            return /^(#|data:|http:\/\/|https:\/\/|file:\/\/\/)/i.test(i) ? n : "url(" + JSON.stringify(o = 0 === i.indexOf("//") ? i : 0 === i.indexOf("/") ? r + i : e + i.replace(/^\.\//, "")) + ")"
        })
    }
}]);
(function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.Pjax = f()}})(function(){var define,module,exports;return (function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
var clone = require('./lib/clone.js')
var executeScripts = require('./lib/execute-scripts.js')
var forEachEls = require("./lib/foreach-els.js")
var newUid = require("./lib/uniqueid.js")

var on = require("./lib/events/on.js")
// var off = require("./lib/events/on.js")
var trigger = require("./lib/events/trigger.js")


var Pjax = function(options) {
    this.firstrun = true

    var parseOptions = require("./lib/proto/parse-options.js");
    parseOptions.apply(this,[options])
    this.log("Pjax options", this.options)

    this.maxUid = this.lastUid = newUid()

    this.parseDOM(document)

    on(window, "popstate", function(st) {
      if (st.state) {
        var opt = clone(this.options)
        opt.url = st.state.url
        opt.title = st.state.title
        opt.history = false
        opt.requestOptions = {};
        if (st.state.uid < this.lastUid) {
          opt.backward = true
        }
        else {
          opt.forward = true
        }
        this.lastUid = st.state.uid

        // @todo implement history cache here, based on uid
        this.loadUrl(st.state.url, opt)
      }
    }.bind(this));

    return this;
  }

Pjax.prototype = {
  log: require("./lib/proto/log.js"),

  getElements: require("./lib/proto/get-elements.js"),

  parseDOM: require("./lib/proto/parse-dom.js"),

  refresh: require("./lib/proto/refresh.js"),

  reload: require("./lib/reload.js"),

  attachLink: require("./lib/proto/attach-link.js"),

  attachForm: require("./lib/proto/attach-form.js"),

  updateStylesheets: require("./lib/update-stylesheets.js"),

  forEachSelectors: function(cb, context, DOMcontext) {
    return require("./lib/foreach-selectors.js").bind(this)(this.options.selectors, cb, context, DOMcontext)
  },

  switchSelectors: function(selectors, fromEl, toEl, options) {
    return require("./lib/switches-selectors.js").bind(this)(this.options.switches, this.options.switchesOptions, selectors, fromEl, toEl, options)
  },


  // too much problem with the code below
  // + it’s too dangerous
//   switchFallback: function(fromEl, toEl) {
//     this.switchSelectors(["head", "body"], fromEl, toEl)
//     // execute script when DOM is like it should be
//     Pjax.executeScripts(document.querySelector("head"))
//     Pjax.executeScripts(document.querySelector("body"))
//   }

  latestChance: function(href) {
    window.location = href
  },

  onSwitch: function() {
    trigger(window, "resize scroll")
  },

  loadContent: function(html, options) {
    var tmpEl = document.implementation.createHTMLDocument("pjax")
    var collectForScriptcomplete = [
      Promise.resolve()
    ];

    // parse HTML attributes to copy them
    // since we are forced to use documentElement.innerHTML (outerHTML can't be used for <html>)
    var htmlRegex = /<html[^>]+>/gi
    var htmlAttribsRegex = /\s?[a-z:]+(?:\=(?:\'|\")[^\'\">]+(?:\'|\"))*/gi
    var matches = html.match(htmlRegex)
    if (matches && matches.length) {
      matches = matches[0].match(htmlAttribsRegex)
      if (matches.length) {
        matches.shift()
        matches.forEach(function(htmlAttrib) {
          var attr = htmlAttrib.trim().split("=")
          if (attr.length === 1) {
            tmpEl.documentElement.setAttribute(attr[0], true)
          }
          else {
            tmpEl.documentElement.setAttribute(attr[0], attr[1].slice(1, -1))
          }
        })
      }
    }

    tmpEl.documentElement.innerHTML = html
    this.log("load content", tmpEl.documentElement.attributes, tmpEl.documentElement.innerHTML.length)

    // Clear out any focused controls before inserting new page contents.
    // we clear focus on non form elements
    if (document.activeElement && !document.activeElement.value) {
      try {
        document.activeElement.blur()
      } catch (e) { }
    }

    this.switchSelectors(this.options.selectors, tmpEl, document, options)

    //reset stylesheets if activated
    if(this.options.reRenderCSS === true){
      this.updateStylesheets.call(this, tmpEl.querySelectorAll('link[rel=stylesheet]'), document.querySelectorAll('link[rel=stylesheet]'));
    }

    // FF bug: Won’t autofocus fields that are inserted via JS.
    // This behavior is incorrect. So if theres no current focus, autofocus
    // the last field.
    //
    // http://www.w3.org/html/wg/drafts/html/master/forms.html
    var autofocusEl = Array.prototype.slice.call(document.querySelectorAll("[autofocus]")).pop()
    if (autofocusEl && document.activeElement !== autofocusEl) {
      autofocusEl.focus();
    }

    // execute scripts when DOM have been completely updated
    this.options.selectors.forEach( function(selector) {
      forEachEls(document.querySelectorAll(selector), function(el) {

        collectForScriptcomplete.push(executeScripts.call(this, el));

      }, this);

    },this);
    // }
    // catch(e) {
    //   if (this.options.debug) {
    //     this.log("Pjax switch fail: ", e)
    //   }
    //   this.switchFallback(tmpEl, document)
    // }
    this.log("waiting for scriptcomplete",collectForScriptcomplete);
    //Fallback! If something can't be loaded or is not loaded correctly -> just force eventing in error
    var timeOutScriptEvent = window.setTimeout( function(){
      trigger(document,"pjax:scriptcomplete pjax:scripttimeout", options)
    }, this.options.scriptloadtimeout)
    Promise.all(collectForScriptcomplete).then(
      //resolved
      function(){
        window.clearTimeout(timeOutScriptEvent);
        trigger(document,"pjax:scriptcomplete pjax:scriptsuccess", options)
      },
      function(){
        window.clearTimeout(timeOutScriptEvent);
        trigger(document,"pjax:scriptcomplete pjax:scripterror", options)
      }
    );


  },

  doRequest: require("./lib/request.js"),

  loadUrl: function(href, options) {
    this.log("load href", href, options)

    trigger(document, "pjax:send", options);

    // Do the request
    this.doRequest(href, options.requestOptions, function(html) {
      // Fail if unable to load HTML via AJAX
      if (html === false) {
        trigger(document,"pjax:complete pjax:error", options)

        return
      }

      // Clear out any focused controls before inserting new page contents.
      document.activeElement.blur()

      try {
        this.loadContent(html, options)
      }
      catch (e) {
        if (!this.options.debug) {
          if (console && console.error) {
            console.error("Pjax switch fail: ", e)
          }
          this.latestChance(href)
          return
        }
        else {
          if (this.options.forceRedirectOnFail) {
            this.latestChance(href);
          }
          throw e;
        }
      }

      if (options.history) {
        if (this.firstrun) {
          this.lastUid = this.maxUid = newUid()
          this.firstrun = false
          window.history.replaceState({
            url: window.location.href,
            title: document.title,
            uid: this.maxUid
          },
          document.title)
        }

        // Update browser history
        this.lastUid = this.maxUid = newUid()
        window.history.pushState({
          url: href,
          title: options.title,
          uid: this.maxUid
        },
          options.title,
          href)
      }

      this.forEachSelectors(function(el) {
        this.parseDOM(el)
      }, this)

      // Fire Events
      trigger(document,"pjax:complete pjax:success", options)

      options.analytics()

      // Scroll page to top on new page load
      if (options.scrollTo !== false) {
        if (options.scrollTo.length > 1) {
          window.scrollTo(options.scrollTo[0], options.scrollTo[1])
        }
        else {
          window.scrollTo(0, options.scrollTo)
        }
      }
    }.bind(this))
  }
}

Pjax.isSupported = require("./lib/is-supported.js");

//arguably could do `if( require("./lib/is-supported.js")()) {` but that might be a little to simple
if (Pjax.isSupported()) {
  module.exports = Pjax
}
// if there isn’t required browser functions, returning stupid api
else {
  var stupidPjax = function() {}
  for (var key in Pjax.prototype) {
    if (Pjax.prototype.hasOwnProperty(key) && typeof Pjax.prototype[key] === "function") {
      stupidPjax[key] = stupidPjax
    }
  }

  module.exports = stupidPjax
}

},{"./lib/clone.js":2,"./lib/events/on.js":4,"./lib/events/trigger.js":5,"./lib/execute-scripts.js":6,"./lib/foreach-els.js":7,"./lib/foreach-selectors.js":8,"./lib/is-supported.js":9,"./lib/proto/attach-form.js":11,"./lib/proto/attach-link.js":12,"./lib/proto/get-elements.js":13,"./lib/proto/log.js":14,"./lib/proto/parse-dom.js":15,"./lib/proto/parse-options.js":17,"./lib/proto/refresh.js":18,"./lib/reload.js":19,"./lib/request.js":20,"./lib/switches-selectors.js":21,"./lib/uniqueid.js":23,"./lib/update-stylesheets.js":24}],2:[function(require,module,exports){
module.exports = function(obj) {
  if (null === obj || "object" != typeof obj) {
    return obj
  }
  var copy = obj.constructor()
  for (var attr in obj) {
    if (obj.hasOwnProperty(attr)) {
      copy[attr] = obj[attr]
    }
  }
  return copy
}

},{}],3:[function(require,module,exports){
module.exports = function(el) {
  var querySelector = this.options.mainScriptElement;
  var code = (el.text || el.textContent || el.innerHTML || "")
  var src = (el.src || "");
  var parent = el.parentNode || document.querySelector(querySelector) || document.documentElement
  var script = document.createElement("script")
  var promise = null;

  this.log("Evaluating Script: ", el);

  if (code.match("document.write")) {
    if (console && console.log) {
      console.log("Script contains document.write. Can’t be executed correctly. Code skipped ", el)
    }
    return false
  }

  promise = new Promise(function(resolve, reject){

    script.type = "text/javascript"
    if (src != "") {
      script.src = src;
      script.onload = resolve;
      script.async = true; // force asynchronous loading of peripheral js
    }

    if (code != "") {
      try {
        script.appendChild(document.createTextNode(code))
      }
      catch (e) {
        // old IEs have funky script nodes
        script.text = code
      }
      resolve();
    }
  });

  this.log('ParentElement => ', parent );

  // execute
  parent.appendChild(script);
  parent.removeChild(script)
  // avoid pollution only in head or body tags
  if (["head","body"].indexOf( parent.tagName.toLowerCase()) > 0) {
  }

  return promise;
}

},{}],4:[function(require,module,exports){
var forEachEls = require("../foreach-els")

module.exports = function(els, events, listener, useCapture) {
  events = (typeof events === "string" ? events.split(" ") : events)

  events.forEach(function(e) {
    forEachEls(els, function(el) {
      el.addEventListener(e, listener, useCapture)
    })
  })
}

},{"../foreach-els":7}],5:[function(require,module,exports){
var forEachEls = require("../foreach-els")

module.exports = function(els, events, opts) {
  events = (typeof events === "string" ? events.split(" ") : events)

  events.forEach(function(e) {
    var event // = new CustomEvent(e) // doesn't everywhere yet
    event = document.createEvent("HTMLEvents")
    event.initEvent(e, true, true)
    event.eventName = e
    if (opts) {
      Object.keys(opts).forEach(function(key) {
        event[key] = opts[key]
      })
    }

    forEachEls(els, function(el) {
      var domFix = false
      if (!el.parentNode && el !== document && el !== window) {
        // THANKS YOU IE (9/10//11 concerned)
        // dispatchEvent doesn't work if element is not in the dom
        domFix = true
        document.body.appendChild(el)
      }
      el.dispatchEvent(event)
      if (domFix) {
        el.parentNode.removeChild(el)
      }
    })
  })
}

},{"../foreach-els":7}],6:[function(require,module,exports){
var forEachEls = require("./foreach-els")
var evalScript = require("./eval-script")
// Finds and executes scripts (used for newly added elements)
// Needed since innerHTML does not run scripts
module.exports = function(el) {

  this.log("Executing scripts for ", el);

  var loadingScripts = [];

  if(el === undefined) return Promise.resolve();

  if (el.tagName.toLowerCase() === "script") {
    evalScript.call(this, el);
  }

  forEachEls(el.querySelectorAll("script"), function(script) {
    if (!script.type || script.type.toLowerCase() === "text/javascript") {
      // if (script.parentNode) {
      //   script.parentNode.removeChild(script)
      // }
      loadingScripts.push(evalScript.call(this, script));
    }
  }, this);

  return Promise.all(loadingScripts);
}

},{"./eval-script":3,"./foreach-els":7}],7:[function(require,module,exports){
/* global HTMLCollection: true */

module.exports = function(els, fn, context) {
  if (els instanceof HTMLCollection || els instanceof NodeList || els instanceof Array) {
    return Array.prototype.forEach.call(els, fn, context)
  }
  // assume simple dom element
  return fn.call(context, els)
}

},{}],8:[function(require,module,exports){
var forEachEls = require("./foreach-els")

module.exports = function(selectors, cb, context, DOMcontext) {
  DOMcontext = DOMcontext || document
  selectors.forEach(function(selector) {
    forEachEls(DOMcontext.querySelectorAll(selector), cb, context)
  })
}

},{"./foreach-els":7}],9:[function(require,module,exports){
module.exports = function() {
  // Borrowed wholesale from https://github.com/defunkt/jquery-pjax
  return window.history &&
    window.history.pushState &&
    window.history.replaceState &&
    // pushState isn’t reliable on iOS until 5.
    !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]\D|WebApps\/.+CFNetwork)/)
}

},{}],10:[function(require,module,exports){
if (!Function.prototype.bind) {
  Function.prototype.bind = function(oThis) {
    if (typeof this !== "function") {
      // closest thing possible to the ECMAScript 5 internal IsCallable function
      throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable")
    }

    var aArgs = Array.prototype.slice.call(arguments, 1)
    var that = this
    var Fnoop = function() {}
    var fBound = function() {
      return that.apply(this instanceof Fnoop && oThis ? this : oThis, aArgs.concat(Array.prototype.slice.call(arguments)))
    }

    Fnoop.prototype = this.prototype
    fBound.prototype = new Fnoop()

    return fBound
  }
}

},{}],11:[function(require,module,exports){
require("../polyfills/Function.prototype.bind")

var on = require("../events/on")
var clone = require("../clone")

var attrClick = "data-pjax-click-state"

var formAction = function(el, event){

  this.options.requestOptions = {
    requestUrl : el.getAttribute('action') || window.location.href,
    requestMethod : el.getAttribute('method') || 'GET',
  }

  //create a testable virtual link of the form action
  var virtLinkElement = document.createElement('a');
  virtLinkElement.setAttribute('href', this.options.requestOptions.requestUrl);

  // Ignore external links.
  if (virtLinkElement.protocol !== window.location.protocol || virtLinkElement.host !== window.location.host) {
    el.setAttribute(attrClick, "external");
    return
  }

  // Ignore click if we are on an anchor on the same page
  if (virtLinkElement.pathname === window.location.pathname && virtLinkElement.hash.length > 0) {
    el.setAttribute(attrClick, "anchor-present");
    return
  }

  // Ignore empty anchor "foo.html#"
  if (virtLinkElement.href === window.location.href.split("#")[0] + "#") {
    el.setAttribute(attrClick, "anchor-empty")
    return
  }

  // if declared as a full reload, just normally submit the form
  if ( this.options.currentUrlFullReload) {
    el.setAttribute(attrClick, "reload");
    return;
  }

  event.preventDefault()
  var nameList = [];
  var paramObject = [];
  for(var elementKey in el.elements) {
    var element = el.elements[elementKey];
    if (!!element.name && element.attributes !== undefined && element.tagName.toLowerCase() !== 'button'){
      if (
        (element.type !== 'checkbox' && element.type !== 'radio') || element.checked
      ) {
        if(nameList.indexOf(element.name) === -1){
          nameList.push(element.name);
          paramObject.push({ name: encodeURIComponent(element.name), value: encodeURIComponent(element.value)});
        }
      }
    }
  }



  //Creating a getString
  var paramsString = (paramObject.map(function(value){return value.name+"="+value.value;})).join('&');

  this.options.requestOptions.requestPayload = paramObject;
  this.options.requestOptions.requestPayloadString = paramsString;

  el.setAttribute(attrClick, "submit");

  this.loadUrl(virtLinkElement.href, clone(this.options))

};

var isDefaultPrevented = function(event) {
  return event.defaultPrevented || event.returnValue === false;
};


module.exports = function(el) {
  var that = this

  on(el, "submit", function(event) {
    if (isDefaultPrevented(event)) {
      return
    }

    formAction.call(that, el, event)
  })

  on(el, "keyup", function(event) {
    if (isDefaultPrevented(event)) {
      return
    }


    if (event.keyCode == 13) {
      formAction.call(that, el, event)
    }
  }.bind(this))
}

},{"../clone":2,"../events/on":4,"../polyfills/Function.prototype.bind":10}],12:[function(require,module,exports){
require("../polyfills/Function.prototype.bind")

var on = require("../events/on")
var clone = require("../clone")

var attrClick = "data-pjax-click-state"
var attrKey = "data-pjax-keyup-state"

var linkAction = function(el, event) {
  // Don’t break browser special behavior on links (like page in new window)
  if (event.which > 1 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
    el.setAttribute(attrClick, "modifier")
    return
  }

  // we do test on href now to prevent unexpected behavior if for some reason
  // user have href that can be dynamically updated

  // Ignore external links.
  if (el.protocol !== window.location.protocol || el.host !== window.location.host) {
    el.setAttribute(attrClick, "external")
    return
  }

  // Ignore click if we are on an anchor on the same page
  if (el.pathname === window.location.pathname && el.hash.length > 0) {
    el.setAttribute(attrClick, "anchor-present")
    return
  }

  // Ignore anchors on the same page (keep native behavior)
  if (el.hash && el.href.replace(el.hash, "") === window.location.href.replace(location.hash, "")) {
    el.setAttribute(attrClick, "anchor")
    return
  }

  // Ignore empty anchor "foo.html#"
  if (el.href === window.location.href.split("#")[0] + "#") {
    el.setAttribute(attrClick, "anchor-empty")
    return
  }

  event.preventDefault()

  // don’t do "nothing" if user try to reload the page by clicking the same link twice
  if (
    this.options.currentUrlFullReload &&
    el.href === window.location.href.split("#")[0]
  ) {
    el.setAttribute(attrClick, "reload")
    this.reload()
    return
  }
  this.options.requestOptions = this.options.requestOptions || {};
  el.setAttribute(attrClick, "load")
  this.loadUrl(el.href, clone(this.options))
}

var isDefaultPrevented = function(event) {
  return event.defaultPrevented || event.returnValue === false;
}

module.exports = function(el) {
  var that = this

  on(el, "click", function(event) {
    if (isDefaultPrevented(event)) {
      return
    }

    linkAction.call(that, el, event)
  })

  on(el, "keyup", function(event) {
    if (isDefaultPrevented(event)) {
      return
    }

    // Don’t break browser special behavior on links (like page in new window)
    if (event.which > 1 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      el.setAttribute(attrKey, "modifier")
      return
    }

    if (event.keyCode == 13) {
      linkAction.call(that, el, event)
    }
  }.bind(this))
}

},{"../clone":2,"../events/on":4,"../polyfills/Function.prototype.bind":10}],13:[function(require,module,exports){
module.exports = function(el) {
  return el.querySelectorAll(this.options.elements)
}

},{}],14:[function(require,module,exports){
module.exports = function() {
  if ((this.options.debug && console)) {
    if (typeof console.log === "function") {
      console.log.apply(console, ['PJAX ->',arguments]);
    }
    // ie is weird
    else if (console.log) {
      console.log(['PJAX ->',arguments]);
    }
  }
}

},{}],15:[function(require,module,exports){
var forEachEls = require("../foreach-els")

var parseElement = require("./parse-element")

module.exports = function(el) {
  forEachEls(this.getElements(el), parseElement, this)
}

},{"../foreach-els":7,"./parse-element":16}],16:[function(require,module,exports){
module.exports = function(el) {
  switch (el.tagName.toLowerCase()) {
  case "a":
    // only attach link if el does not already have link attached
    if (!el.hasAttribute('data-pjax-click-state')) {
      this.attachLink(el)
    }
    break

    case "form":
      // only attach link if el does not already have link attached
      if (!el.hasAttribute('data-pjax-click-state')) {
        this.attachForm(el)
      }
    break

  default:
    throw "Pjax can only be applied on <a> or <form> submit"
  }
}

},{}],17:[function(require,module,exports){
/* global _gaq: true, ga: true */

module.exports = function(options){
  this.options = options
  this.options.elements = this.options.elements || "a[href], form[action]",
  this.options.reRenderCSS = this.options.reRenderCSS || false,
  this.options.forceRedirectOnFail = this.options.forceRedirectOnFail || false,
  this.options.scriptloadtimeout = this.options.scriptloadtimeout || 1000,
  this.options.mainScriptElement = this.options.mainScriptElement || "head"
  this.options.selectors = this.options.selectors || ["title", ".js-Pjax"]
  this.options.switches = this.options.switches || {}
  this.options.switchesOptions = this.options.switchesOptions || {}
  this.options.history = this.options.history || true
  this.options.analytics = this.options.analytics || function() {
    // options.backward or options.foward can be true or undefined
    // by default, we do track back/foward hit
    // https://productforums.google.com/forum/#!topic/analytics/WVwMDjLhXYk
    if (window._gaq) {
      _gaq.push(["_trackPageview"])
    }
    if (window.ga) {
      ga("send", "pageview", {page: location.pathname, title: document.title})
    }
  }
  this.options.scrollTo = (typeof this.options.scrollTo === 'undefined') ? 0 : this.options.scrollTo;
  this.options.cacheBust = (typeof this.options.cacheBust === 'undefined') ? true : this.options.cacheBust
  this.options.debug = this.options.debug || false

  // we can’t replace body.outerHTML or head.outerHTML
  // it create a bug where new body or new head are created in the dom
  // if you set head.outerHTML, a new body tag is appended, so the dom get 2 body
  // & it break the switchFallback which replace head & body
  if (!this.options.switches.head) {
    this.options.switches.head = this.switchElementsAlt
  }
  if (!this.options.switches.body) {
    this.options.switches.body = this.switchElementsAlt
  }
  if (typeof options.analytics !== "function") {
    options.analytics = function() {}
  }
}

},{}],18:[function(require,module,exports){
module.exports = function(el) {
  this.parseDOM(el || document)
}

},{}],19:[function(require,module,exports){
module.exports = function() {
  window.location.reload()
}

},{}],20:[function(require,module,exports){
module.exports = function(location, options, callback) {
  options = options || {};
  var requestMethod = options.requestMethod || "GET";
  var requestPayload = options.requestPayloadString || null;
  var request = new XMLHttpRequest()

  request.onreadystatechange = function() {
    if (request.readyState === 4) {
      if (request.status === 200) {
        callback(request.responseText, request)
      }
      else {
        callback(null, request)
      }
    }
  }

  // Add a timestamp as part of the query string if cache busting is enabled
  if (this.options.cacheBust) {
    location += (!/[?&]/.test(location) ? "?" : "&") + new Date().getTime()
  }

  request.open(requestMethod.toUpperCase(), location, true)
  request.setRequestHeader("X-Requested-With", "XMLHttpRequest")

  // Add the request payload if available
  if (options.requestPayloadString != undefined && options.requestPayloadString != "") {
    // Send the proper header information along with the request
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  }

  request.send(requestPayload)

  return request
}

},{}],21:[function(require,module,exports){
var forEachEls = require("./foreach-els")

var defaultSwitches = require("./switches")

module.exports = function(switches, switchesOptions, selectors, fromEl, toEl, options) {
  selectors.forEach(function(selector) {
    var newEls = fromEl.querySelectorAll(selector)
    var oldEls = toEl.querySelectorAll(selector)
    if (this.log) {
      this.log("Pjax switch", selector, newEls, oldEls)
    }
    if (newEls.length !== oldEls.length) {
      // forEachEls(newEls, function(el) {
      //   this.log("newEl", el, el.outerHTML)
      // }, this)
      // forEachEls(oldEls, function(el) {
      //   this.log("oldEl", el, el.outerHTML)
      // }, this)
      throw "DOM doesn’t look the same on new loaded page: ’" + selector + "’ - new " + newEls.length + ", old " + oldEls.length
    }

    forEachEls(newEls, function(newEl, i) {
      var oldEl = oldEls[i]
      if (this.log) {
        this.log("newEl", newEl, "oldEl", oldEl)
      }
      if (switches[selector]) {
        switches[selector].bind(this)(oldEl, newEl, options, switchesOptions[selector])
      }
      else {
        defaultSwitches.outerHTML.bind(this)(oldEl, newEl, options)
      }
    }, this)
  }, this)
}

},{"./foreach-els":7,"./switches":22}],22:[function(require,module,exports){
var on = require("./events/on.js")
// var off = require("./lib/events/on.js")
// var trigger = require("./lib/events/trigger.js")


module.exports = {
  outerHTML: function(oldEl, newEl) {
    oldEl.outerHTML = newEl.outerHTML
    this.onSwitch()
  },

  innerHTML: function(oldEl, newEl) {
    oldEl.innerHTML = newEl.innerHTML
    oldEl.className = newEl.className
    this.onSwitch()
  },

  sideBySide: function(oldEl, newEl, options, switchOptions) {
    var forEach = Array.prototype.forEach
    var elsToRemove = []
    var elsToAdd = []
    var fragToAppend = document.createDocumentFragment()
    // height transition are shitty on safari
    // so commented for now (until I found something ?)
    // var relevantHeight = 0
    var animationEventNames = "animationend webkitAnimationEnd MSAnimationEnd oanimationend"
    var animatedElsNumber = 0
    var sexyAnimationEnd = function(e) {
          if (e.target != e.currentTarget) {
            // end triggered by an animation on a child
            return
          }

          animatedElsNumber--
          if (animatedElsNumber <= 0 && elsToRemove) {
            elsToRemove.forEach(function(el) {
              // browsing quickly can make the el
              // already removed by last page update ?
              if (el.parentNode) {
                el.parentNode.removeChild(el)
              }
            })

            elsToAdd.forEach(function(el) {
              el.className = el.className.replace(el.getAttribute("data-pjax-classes"), "")
              el.removeAttribute("data-pjax-classes")
              // Pjax.off(el, animationEventNames, sexyAnimationEnd, true)
            })

            elsToAdd = null // free memory
            elsToRemove = null // free memory

            // assume the height is now useless (avoid bug since there is overflow hidden on the parent)
            // oldEl.style.height = "auto"

            // this is to trigger some repaint (example: picturefill)
            this.onSwitch()
            // Pjax.trigger(window, "scroll")
          }
        }.bind(this)

    // Force height to be able to trigger css animation
    // here we get the relevant height
    // oldEl.parentNode.appendChild(newEl)
    // relevantHeight = newEl.getBoundingClientRect().height
    // oldEl.parentNode.removeChild(newEl)
    // oldEl.style.height = oldEl.getBoundingClientRect().height + "px"

    switchOptions = switchOptions || {}

    forEach.call(oldEl.childNodes, function(el) {
      elsToRemove.push(el)
      if (el.classList && !el.classList.contains("js-Pjax-remove")) {
        // for fast switch, clean element that just have been added, & not cleaned yet.
        if (el.hasAttribute("data-pjax-classes")) {
          el.className = el.className.replace(el.getAttribute("data-pjax-classes"), "")
          el.removeAttribute("data-pjax-classes")
        }
        el.classList.add("js-Pjax-remove")
        if (switchOptions.callbacks && switchOptions.callbacks.removeElement) {
          switchOptions.callbacks.removeElement(el)
        }
        if (switchOptions.classNames) {
          el.className += " " + switchOptions.classNames.remove + " " + (options.backward ? switchOptions.classNames.backward : switchOptions.classNames.forward)
        }
        animatedElsNumber++
        on(el, animationEventNames, sexyAnimationEnd, true)
      }
    })

    forEach.call(newEl.childNodes, function(el) {
      if (el.classList) {
        var addClasses = ""
        if (switchOptions.classNames) {
          addClasses = " js-Pjax-add " + switchOptions.classNames.add + " " + (options.backward ? switchOptions.classNames.forward : switchOptions.classNames.backward)
        }
        if (switchOptions.callbacks && switchOptions.callbacks.addElement) {
          switchOptions.callbacks.addElement(el)
        }
        el.className += addClasses
        el.setAttribute("data-pjax-classes", addClasses)
        elsToAdd.push(el)
        fragToAppend.appendChild(el)
        animatedElsNumber++
        on(el, animationEventNames, sexyAnimationEnd, true)
      }
    })

    // pass all className of the parent
    oldEl.className = newEl.className
    oldEl.appendChild(fragToAppend)

    // oldEl.style.height = relevantHeight + "px"
  }
}

},{"./events/on.js":4}],23:[function(require,module,exports){
module.exports = (function() {
  var counter = 0
  return function() {
    var id = ("pjax" + (new Date().getTime())) + "_" + counter
    counter++
    return id
  }
})()

},{}],24:[function(require,module,exports){
var forEachEls = require("./foreach-els")

module.exports = function(elements, oldElements) {
   this.log("styleheets old elements", oldElements);
   this.log("styleheets new elements", elements);
  var toArray = function(enumerable){
      var arr = [];
      for(var i = enumerable.length; i--; arr.unshift(enumerable[i]));
      return arr;
  };
  forEachEls(elements, function(newEl, i) {
    var oldElementsArray = toArray(oldElements);
    var resemblingOld = oldElementsArray.reduce(function(acc, oldEl){
      acc = ((oldEl.href === newEl.href) ? oldEl : acc);
      return acc;
    }, null);

    if(resemblingOld !== null){
      if (this.log) {
        this.log("old stylesheet found not resetting");
      }
    } else {
      if (this.log) {
        this.log("new stylesheet => add to head");
      }
      var head = document.getElementsByTagName( 'head' )[0],
       link = document.createElement( 'link' );
        link.setAttribute( 'href', newEl.href );
        link.setAttribute( 'rel', 'stylesheet' );
        link.setAttribute( 'type', 'text/css' );
        head.appendChild(link);
    }
  }, this);

}

},{"./foreach-els":7}]},{},[1])(1)
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIi4uLy4uL25vZGUvbGliL25vZGVfbW9kdWxlcy9icm93c2VyaWZ5L25vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJpbmRleC5qcyIsImxpYi9jbG9uZS5qcyIsImxpYi9ldmFsLXNjcmlwdC5qcyIsImxpYi9ldmVudHMvb24uanMiLCJsaWIvZXZlbnRzL3RyaWdnZXIuanMiLCJsaWIvZXhlY3V0ZS1zY3JpcHRzLmpzIiwibGliL2ZvcmVhY2gtZWxzLmpzIiwibGliL2ZvcmVhY2gtc2VsZWN0b3JzLmpzIiwibGliL2lzLXN1cHBvcnRlZC5qcyIsImxpYi9wb2x5ZmlsbHMvRnVuY3Rpb24ucHJvdG90eXBlLmJpbmQuanMiLCJsaWIvcHJvdG8vYXR0YWNoLWZvcm0uanMiLCJsaWIvcHJvdG8vYXR0YWNoLWxpbmsuanMiLCJsaWIvcHJvdG8vZ2V0LWVsZW1lbnRzLmpzIiwibGliL3Byb3RvL2xvZy5qcyIsImxpYi9wcm90by9wYXJzZS1kb20uanMiLCJsaWIvcHJvdG8vcGFyc2UtZWxlbWVudC5qcyIsImxpYi9wcm90by9wYXJzZS1vcHRpb25zLmpzIiwibGliL3Byb3RvL3JlZnJlc2guanMiLCJsaWIvcmVsb2FkLmpzIiwibGliL3JlcXVlc3QuanMiLCJsaWIvc3dpdGNoZXMtc2VsZWN0b3JzLmpzIiwibGliL3N3aXRjaGVzLmpzIiwibGliL3VuaXF1ZWlkLmpzIiwibGliL3VwZGF0ZS1zdHlsZXNoZWV0cy5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQ0FBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUN0UkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDWkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNqREE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ1hBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDL0JBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQzNCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNUQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDUkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ1JBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNwQkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNwR0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ3pGQTtBQUNBO0FBQ0E7QUFDQTs7QUNIQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDWEE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNQQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDcEJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQzFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNIQTtBQUNBO0FBQ0E7QUFDQTs7QUNIQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDbkNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNuQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUNuSEE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ1JBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiJnZW5lcmF0ZWQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uIGUodCxuLHIpe2Z1bmN0aW9uIHMobyx1KXtpZighbltvXSl7aWYoIXRbb10pe3ZhciBhPXR5cGVvZiByZXF1aXJlPT1cImZ1bmN0aW9uXCImJnJlcXVpcmU7aWYoIXUmJmEpcmV0dXJuIGEobywhMCk7aWYoaSlyZXR1cm4gaShvLCEwKTt2YXIgZj1uZXcgRXJyb3IoXCJDYW5ub3QgZmluZCBtb2R1bGUgJ1wiK28rXCInXCIpO3Rocm93IGYuY29kZT1cIk1PRFVMRV9OT1RfRk9VTkRcIixmfXZhciBsPW5bb109e2V4cG9ydHM6e319O3Rbb11bMF0uY2FsbChsLmV4cG9ydHMsZnVuY3Rpb24oZSl7dmFyIG49dFtvXVsxXVtlXTtyZXR1cm4gcyhuP246ZSl9LGwsbC5leHBvcnRzLGUsdCxuLHIpfXJldHVybiBuW29dLmV4cG9ydHN9dmFyIGk9dHlwZW9mIHJlcXVpcmU9PVwiZnVuY3Rpb25cIiYmcmVxdWlyZTtmb3IodmFyIG89MDtvPHIubGVuZ3RoO28rKylzKHJbb10pO3JldHVybiBzfSkiLCJ2YXIgY2xvbmUgPSByZXF1aXJlKCcuL2xpYi9jbG9uZS5qcycpXG52YXIgZXhlY3V0ZVNjcmlwdHMgPSByZXF1aXJlKCcuL2xpYi9leGVjdXRlLXNjcmlwdHMuanMnKVxudmFyIGZvckVhY2hFbHMgPSByZXF1aXJlKFwiLi9saWIvZm9yZWFjaC1lbHMuanNcIilcbnZhciBuZXdVaWQgPSByZXF1aXJlKFwiLi9saWIvdW5pcXVlaWQuanNcIilcblxudmFyIG9uID0gcmVxdWlyZShcIi4vbGliL2V2ZW50cy9vbi5qc1wiKVxuLy8gdmFyIG9mZiA9IHJlcXVpcmUoXCIuL2xpYi9ldmVudHMvb24uanNcIilcbnZhciB0cmlnZ2VyID0gcmVxdWlyZShcIi4vbGliL2V2ZW50cy90cmlnZ2VyLmpzXCIpXG5cblxudmFyIFBqYXggPSBmdW5jdGlvbihvcHRpb25zKSB7XG4gICAgdGhpcy5maXJzdHJ1biA9IHRydWVcblxuICAgIHZhciBwYXJzZU9wdGlvbnMgPSByZXF1aXJlKFwiLi9saWIvcHJvdG8vcGFyc2Utb3B0aW9ucy5qc1wiKTtcbiAgICBwYXJzZU9wdGlvbnMuYXBwbHkodGhpcyxbb3B0aW9uc10pXG4gICAgdGhpcy5sb2coXCJQamF4IG9wdGlvbnNcIiwgdGhpcy5vcHRpb25zKVxuXG4gICAgdGhpcy5tYXhVaWQgPSB0aGlzLmxhc3RVaWQgPSBuZXdVaWQoKVxuXG4gICAgdGhpcy5wYXJzZURPTShkb2N1bWVudClcblxuICAgIG9uKHdpbmRvdywgXCJwb3BzdGF0ZVwiLCBmdW5jdGlvbihzdCkge1xuICAgICAgaWYgKHN0LnN0YXRlKSB7XG4gICAgICAgIHZhciBvcHQgPSBjbG9uZSh0aGlzLm9wdGlvbnMpXG4gICAgICAgIG9wdC51cmwgPSBzdC5zdGF0ZS51cmxcbiAgICAgICAgb3B0LnRpdGxlID0gc3Quc3RhdGUudGl0bGVcbiAgICAgICAgb3B0Lmhpc3RvcnkgPSBmYWxzZVxuICAgICAgICBvcHQucmVxdWVzdE9wdGlvbnMgPSB7fTtcbiAgICAgICAgaWYgKHN0LnN0YXRlLnVpZCA8IHRoaXMubGFzdFVpZCkge1xuICAgICAgICAgIG9wdC5iYWNrd2FyZCA9IHRydWVcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICBvcHQuZm9yd2FyZCA9IHRydWVcbiAgICAgICAgfVxuICAgICAgICB0aGlzLmxhc3RVaWQgPSBzdC5zdGF0ZS51aWRcblxuICAgICAgICAvLyBAdG9kbyBpbXBsZW1lbnQgaGlzdG9yeSBjYWNoZSBoZXJlLCBiYXNlZCBvbiB1aWRcbiAgICAgICAgdGhpcy5sb2FkVXJsKHN0LnN0YXRlLnVybCwgb3B0KVxuICAgICAgfVxuICAgIH0uYmluZCh0aGlzKSk7XG5cbiAgICByZXR1cm4gdGhpcztcbiAgfVxuXG5QamF4LnByb3RvdHlwZSA9IHtcbiAgbG9nOiByZXF1aXJlKFwiLi9saWIvcHJvdG8vbG9nLmpzXCIpLFxuXG4gIGdldEVsZW1lbnRzOiByZXF1aXJlKFwiLi9saWIvcHJvdG8vZ2V0LWVsZW1lbnRzLmpzXCIpLFxuXG4gIHBhcnNlRE9NOiByZXF1aXJlKFwiLi9saWIvcHJvdG8vcGFyc2UtZG9tLmpzXCIpLFxuXG4gIHJlZnJlc2g6IHJlcXVpcmUoXCIuL2xpYi9wcm90by9yZWZyZXNoLmpzXCIpLFxuXG4gIHJlbG9hZDogcmVxdWlyZShcIi4vbGliL3JlbG9hZC5qc1wiKSxcblxuICBhdHRhY2hMaW5rOiByZXF1aXJlKFwiLi9saWIvcHJvdG8vYXR0YWNoLWxpbmsuanNcIiksXG5cbiAgYXR0YWNoRm9ybTogcmVxdWlyZShcIi4vbGliL3Byb3RvL2F0dGFjaC1mb3JtLmpzXCIpLFxuXG4gIHVwZGF0ZVN0eWxlc2hlZXRzOiByZXF1aXJlKFwiLi9saWIvdXBkYXRlLXN0eWxlc2hlZXRzLmpzXCIpLFxuXG4gIGZvckVhY2hTZWxlY3RvcnM6IGZ1bmN0aW9uKGNiLCBjb250ZXh0LCBET01jb250ZXh0KSB7XG4gICAgcmV0dXJuIHJlcXVpcmUoXCIuL2xpYi9mb3JlYWNoLXNlbGVjdG9ycy5qc1wiKS5iaW5kKHRoaXMpKHRoaXMub3B0aW9ucy5zZWxlY3RvcnMsIGNiLCBjb250ZXh0LCBET01jb250ZXh0KVxuICB9LFxuXG4gIHN3aXRjaFNlbGVjdG9yczogZnVuY3Rpb24oc2VsZWN0b3JzLCBmcm9tRWwsIHRvRWwsIG9wdGlvbnMpIHtcbiAgICByZXR1cm4gcmVxdWlyZShcIi4vbGliL3N3aXRjaGVzLXNlbGVjdG9ycy5qc1wiKS5iaW5kKHRoaXMpKHRoaXMub3B0aW9ucy5zd2l0Y2hlcywgdGhpcy5vcHRpb25zLnN3aXRjaGVzT3B0aW9ucywgc2VsZWN0b3JzLCBmcm9tRWwsIHRvRWwsIG9wdGlvbnMpXG4gIH0sXG5cblxuICAvLyB0b28gbXVjaCBwcm9ibGVtIHdpdGggdGhlIGNvZGUgYmVsb3dcbiAgLy8gKyBpdOKAmXMgdG9vIGRhbmdlcm91c1xuLy8gICBzd2l0Y2hGYWxsYmFjazogZnVuY3Rpb24oZnJvbUVsLCB0b0VsKSB7XG4vLyAgICAgdGhpcy5zd2l0Y2hTZWxlY3RvcnMoW1wiaGVhZFwiLCBcImJvZHlcIl0sIGZyb21FbCwgdG9FbClcbi8vICAgICAvLyBleGVjdXRlIHNjcmlwdCB3aGVuIERPTSBpcyBsaWtlIGl0IHNob3VsZCBiZVxuLy8gICAgIFBqYXguZXhlY3V0ZVNjcmlwdHMoZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcImhlYWRcIikpXG4vLyAgICAgUGpheC5leGVjdXRlU2NyaXB0cyhkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiYm9keVwiKSlcbi8vICAgfVxuXG4gIGxhdGVzdENoYW5jZTogZnVuY3Rpb24oaHJlZikge1xuICAgIHdpbmRvdy5sb2NhdGlvbiA9IGhyZWZcbiAgfSxcblxuICBvblN3aXRjaDogZnVuY3Rpb24oKSB7XG4gICAgdHJpZ2dlcih3aW5kb3csIFwicmVzaXplIHNjcm9sbFwiKVxuICB9LFxuXG4gIGxvYWRDb250ZW50OiBmdW5jdGlvbihodG1sLCBvcHRpb25zKSB7XG4gICAgdmFyIHRtcEVsID0gZG9jdW1lbnQuaW1wbGVtZW50YXRpb24uY3JlYXRlSFRNTERvY3VtZW50KFwicGpheFwiKVxuICAgIHZhciBjb2xsZWN0Rm9yU2NyaXB0Y29tcGxldGUgPSBbXG4gICAgICBQcm9taXNlLnJlc29sdmUoKVxuICAgIF07XG5cbiAgICAvLyBwYXJzZSBIVE1MIGF0dHJpYnV0ZXMgdG8gY29weSB0aGVtXG4gICAgLy8gc2luY2Ugd2UgYXJlIGZvcmNlZCB0byB1c2UgZG9jdW1lbnRFbGVtZW50LmlubmVySFRNTCAob3V0ZXJIVE1MIGNhbid0IGJlIHVzZWQgZm9yIDxodG1sPilcbiAgICB2YXIgaHRtbFJlZ2V4ID0gLzxodG1sW14+XSs+L2dpXG4gICAgdmFyIGh0bWxBdHRyaWJzUmVnZXggPSAvXFxzP1thLXo6XSsoPzpcXD0oPzpcXCd8XFxcIilbXlxcJ1xcXCI+XSsoPzpcXCd8XFxcIikpKi9naVxuICAgIHZhciBtYXRjaGVzID0gaHRtbC5tYXRjaChodG1sUmVnZXgpXG4gICAgaWYgKG1hdGNoZXMgJiYgbWF0Y2hlcy5sZW5ndGgpIHtcbiAgICAgIG1hdGNoZXMgPSBtYXRjaGVzWzBdLm1hdGNoKGh0bWxBdHRyaWJzUmVnZXgpXG4gICAgICBpZiAobWF0Y2hlcy5sZW5ndGgpIHtcbiAgICAgICAgbWF0Y2hlcy5zaGlmdCgpXG4gICAgICAgIG1hdGNoZXMuZm9yRWFjaChmdW5jdGlvbihodG1sQXR0cmliKSB7XG4gICAgICAgICAgdmFyIGF0dHIgPSBodG1sQXR0cmliLnRyaW0oKS5zcGxpdChcIj1cIilcbiAgICAgICAgICBpZiAoYXR0ci5sZW5ndGggPT09IDEpIHtcbiAgICAgICAgICAgIHRtcEVsLmRvY3VtZW50RWxlbWVudC5zZXRBdHRyaWJ1dGUoYXR0clswXSwgdHJ1ZSlcbiAgICAgICAgICB9XG4gICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0bXBFbC5kb2N1bWVudEVsZW1lbnQuc2V0QXR0cmlidXRlKGF0dHJbMF0sIGF0dHJbMV0uc2xpY2UoMSwgLTEpKVxuICAgICAgICAgIH1cbiAgICAgICAgfSlcbiAgICAgIH1cbiAgICB9XG5cbiAgICB0bXBFbC5kb2N1bWVudEVsZW1lbnQuaW5uZXJIVE1MID0gaHRtbFxuICAgIHRoaXMubG9nKFwibG9hZCBjb250ZW50XCIsIHRtcEVsLmRvY3VtZW50RWxlbWVudC5hdHRyaWJ1dGVzLCB0bXBFbC5kb2N1bWVudEVsZW1lbnQuaW5uZXJIVE1MLmxlbmd0aClcblxuICAgIC8vIENsZWFyIG91dCBhbnkgZm9jdXNlZCBjb250cm9scyBiZWZvcmUgaW5zZXJ0aW5nIG5ldyBwYWdlIGNvbnRlbnRzLlxuICAgIC8vIHdlIGNsZWFyIGZvY3VzIG9uIG5vbiBmb3JtIGVsZW1lbnRzXG4gICAgaWYgKGRvY3VtZW50LmFjdGl2ZUVsZW1lbnQgJiYgIWRvY3VtZW50LmFjdGl2ZUVsZW1lbnQudmFsdWUpIHtcbiAgICAgIHRyeSB7XG4gICAgICAgIGRvY3VtZW50LmFjdGl2ZUVsZW1lbnQuYmx1cigpXG4gICAgICB9IGNhdGNoIChlKSB7IH1cbiAgICB9XG5cbiAgICB0aGlzLnN3aXRjaFNlbGVjdG9ycyh0aGlzLm9wdGlvbnMuc2VsZWN0b3JzLCB0bXBFbCwgZG9jdW1lbnQsIG9wdGlvbnMpXG5cbiAgICAvL3Jlc2V0IHN0eWxlc2hlZXRzIGlmIGFjdGl2YXRlZFxuICAgIGlmKHRoaXMub3B0aW9ucy5yZVJlbmRlckNTUyA9PT0gdHJ1ZSl7XG4gICAgICB0aGlzLnVwZGF0ZVN0eWxlc2hlZXRzLmNhbGwodGhpcywgdG1wRWwucXVlcnlTZWxlY3RvckFsbCgnbGlua1tyZWw9c3R5bGVzaGVldF0nKSwgZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgnbGlua1tyZWw9c3R5bGVzaGVldF0nKSk7XG4gICAgfVxuXG4gICAgLy8gRkYgYnVnOiBXb27igJl0IGF1dG9mb2N1cyBmaWVsZHMgdGhhdCBhcmUgaW5zZXJ0ZWQgdmlhIEpTLlxuICAgIC8vIFRoaXMgYmVoYXZpb3IgaXMgaW5jb3JyZWN0LiBTbyBpZiB0aGVyZXMgbm8gY3VycmVudCBmb2N1cywgYXV0b2ZvY3VzXG4gICAgLy8gdGhlIGxhc3QgZmllbGQuXG4gICAgLy9cbiAgICAvLyBodHRwOi8vd3d3LnczLm9yZy9odG1sL3dnL2RyYWZ0cy9odG1sL21hc3Rlci9mb3Jtcy5odG1sXG4gICAgdmFyIGF1dG9mb2N1c0VsID0gQXJyYXkucHJvdG90eXBlLnNsaWNlLmNhbGwoZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbChcIlthdXRvZm9jdXNdXCIpKS5wb3AoKVxuICAgIGlmIChhdXRvZm9jdXNFbCAmJiBkb2N1bWVudC5hY3RpdmVFbGVtZW50ICE9PSBhdXRvZm9jdXNFbCkge1xuICAgICAgYXV0b2ZvY3VzRWwuZm9jdXMoKTtcbiAgICB9XG5cbiAgICAvLyBleGVjdXRlIHNjcmlwdHMgd2hlbiBET00gaGF2ZSBiZWVuIGNvbXBsZXRlbHkgdXBkYXRlZFxuICAgIHRoaXMub3B0aW9ucy5zZWxlY3RvcnMuZm9yRWFjaCggZnVuY3Rpb24oc2VsZWN0b3IpIHtcbiAgICAgIGZvckVhY2hFbHMoZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbChzZWxlY3RvciksIGZ1bmN0aW9uKGVsKSB7XG5cbiAgICAgICAgY29sbGVjdEZvclNjcmlwdGNvbXBsZXRlLnB1c2goZXhlY3V0ZVNjcmlwdHMuY2FsbCh0aGlzLCBlbCkpO1xuXG4gICAgICB9LCB0aGlzKTtcblxuICAgIH0sdGhpcyk7XG4gICAgLy8gfVxuICAgIC8vIGNhdGNoKGUpIHtcbiAgICAvLyAgIGlmICh0aGlzLm9wdGlvbnMuZGVidWcpIHtcbiAgICAvLyAgICAgdGhpcy5sb2coXCJQamF4IHN3aXRjaCBmYWlsOiBcIiwgZSlcbiAgICAvLyAgIH1cbiAgICAvLyAgIHRoaXMuc3dpdGNoRmFsbGJhY2sodG1wRWwsIGRvY3VtZW50KVxuICAgIC8vIH1cbiAgICB0aGlzLmxvZyhcIndhaXRpbmcgZm9yIHNjcmlwdGNvbXBsZXRlXCIsY29sbGVjdEZvclNjcmlwdGNvbXBsZXRlKTtcbiAgICAvL0ZhbGxiYWNrISBJZiBzb21ldGhpbmcgY2FuJ3QgYmUgbG9hZGVkIG9yIGlzIG5vdCBsb2FkZWQgY29ycmVjdGx5IC0+IGp1c3QgZm9yY2UgZXZlbnRpbmcgaW4gZXJyb3JcbiAgICB2YXIgdGltZU91dFNjcmlwdEV2ZW50ID0gd2luZG93LnNldFRpbWVvdXQoIGZ1bmN0aW9uKCl7XG4gICAgICB0cmlnZ2VyKGRvY3VtZW50LFwicGpheDpzY3JpcHRjb21wbGV0ZSBwamF4OnNjcmlwdHRpbWVvdXRcIiwgb3B0aW9ucylcbiAgICB9LCB0aGlzLm9wdGlvbnMuc2NyaXB0bG9hZHRpbWVvdXQpXG4gICAgUHJvbWlzZS5hbGwoY29sbGVjdEZvclNjcmlwdGNvbXBsZXRlKS50aGVuKFxuICAgICAgLy9yZXNvbHZlZFxuICAgICAgZnVuY3Rpb24oKXtcbiAgICAgICAgd2luZG93LmNsZWFyVGltZW91dCh0aW1lT3V0U2NyaXB0RXZlbnQpO1xuICAgICAgICB0cmlnZ2VyKGRvY3VtZW50LFwicGpheDpzY3JpcHRjb21wbGV0ZSBwamF4OnNjcmlwdHN1Y2Nlc3NcIiwgb3B0aW9ucylcbiAgICAgIH0sXG4gICAgICBmdW5jdGlvbigpe1xuICAgICAgICB3aW5kb3cuY2xlYXJUaW1lb3V0KHRpbWVPdXRTY3JpcHRFdmVudCk7XG4gICAgICAgIHRyaWdnZXIoZG9jdW1lbnQsXCJwamF4OnNjcmlwdGNvbXBsZXRlIHBqYXg6c2NyaXB0ZXJyb3JcIiwgb3B0aW9ucylcbiAgICAgIH1cbiAgICApO1xuXG5cbiAgfSxcblxuICBkb1JlcXVlc3Q6IHJlcXVpcmUoXCIuL2xpYi9yZXF1ZXN0LmpzXCIpLFxuXG4gIGxvYWRVcmw6IGZ1bmN0aW9uKGhyZWYsIG9wdGlvbnMpIHtcbiAgICB0aGlzLmxvZyhcImxvYWQgaHJlZlwiLCBocmVmLCBvcHRpb25zKVxuXG4gICAgdHJpZ2dlcihkb2N1bWVudCwgXCJwamF4OnNlbmRcIiwgb3B0aW9ucyk7XG5cbiAgICAvLyBEbyB0aGUgcmVxdWVzdFxuICAgIHRoaXMuZG9SZXF1ZXN0KGhyZWYsIG9wdGlvbnMucmVxdWVzdE9wdGlvbnMsIGZ1bmN0aW9uKGh0bWwpIHtcbiAgICAgIC8vIEZhaWwgaWYgdW5hYmxlIHRvIGxvYWQgSFRNTCB2aWEgQUpBWFxuICAgICAgaWYgKGh0bWwgPT09IGZhbHNlKSB7XG4gICAgICAgIHRyaWdnZXIoZG9jdW1lbnQsXCJwamF4OmNvbXBsZXRlIHBqYXg6ZXJyb3JcIiwgb3B0aW9ucylcblxuICAgICAgICByZXR1cm5cbiAgICAgIH1cblxuICAgICAgLy8gQ2xlYXIgb3V0IGFueSBmb2N1c2VkIGNvbnRyb2xzIGJlZm9yZSBpbnNlcnRpbmcgbmV3IHBhZ2UgY29udGVudHMuXG4gICAgICBkb2N1bWVudC5hY3RpdmVFbGVtZW50LmJsdXIoKVxuXG4gICAgICB0cnkge1xuICAgICAgICB0aGlzLmxvYWRDb250ZW50KGh0bWwsIG9wdGlvbnMpXG4gICAgICB9XG4gICAgICBjYXRjaCAoZSkge1xuICAgICAgICBpZiAoIXRoaXMub3B0aW9ucy5kZWJ1Zykge1xuICAgICAgICAgIGlmIChjb25zb2xlICYmIGNvbnNvbGUuZXJyb3IpIHtcbiAgICAgICAgICAgIGNvbnNvbGUuZXJyb3IoXCJQamF4IHN3aXRjaCBmYWlsOiBcIiwgZSlcbiAgICAgICAgICB9XG4gICAgICAgICAgdGhpcy5sYXRlc3RDaGFuY2UoaHJlZilcbiAgICAgICAgICByZXR1cm5cbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICBpZiAodGhpcy5vcHRpb25zLmZvcmNlUmVkaXJlY3RPbkZhaWwpIHtcbiAgICAgICAgICAgIHRoaXMubGF0ZXN0Q2hhbmNlKGhyZWYpO1xuICAgICAgICAgIH1cbiAgICAgICAgICB0aHJvdyBlO1xuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIGlmIChvcHRpb25zLmhpc3RvcnkpIHtcbiAgICAgICAgaWYgKHRoaXMuZmlyc3RydW4pIHtcbiAgICAgICAgICB0aGlzLmxhc3RVaWQgPSB0aGlzLm1heFVpZCA9IG5ld1VpZCgpXG4gICAgICAgICAgdGhpcy5maXJzdHJ1biA9IGZhbHNlXG4gICAgICAgICAgd2luZG93Lmhpc3RvcnkucmVwbGFjZVN0YXRlKHtcbiAgICAgICAgICAgIHVybDogd2luZG93LmxvY2F0aW9uLmhyZWYsXG4gICAgICAgICAgICB0aXRsZTogZG9jdW1lbnQudGl0bGUsXG4gICAgICAgICAgICB1aWQ6IHRoaXMubWF4VWlkXG4gICAgICAgICAgfSxcbiAgICAgICAgICBkb2N1bWVudC50aXRsZSlcbiAgICAgICAgfVxuXG4gICAgICAgIC8vIFVwZGF0ZSBicm93c2VyIGhpc3RvcnlcbiAgICAgICAgdGhpcy5sYXN0VWlkID0gdGhpcy5tYXhVaWQgPSBuZXdVaWQoKVxuICAgICAgICB3aW5kb3cuaGlzdG9yeS5wdXNoU3RhdGUoe1xuICAgICAgICAgIHVybDogaHJlZixcbiAgICAgICAgICB0aXRsZTogb3B0aW9ucy50aXRsZSxcbiAgICAgICAgICB1aWQ6IHRoaXMubWF4VWlkXG4gICAgICAgIH0sXG4gICAgICAgICAgb3B0aW9ucy50aXRsZSxcbiAgICAgICAgICBocmVmKVxuICAgICAgfVxuXG4gICAgICB0aGlzLmZvckVhY2hTZWxlY3RvcnMoZnVuY3Rpb24oZWwpIHtcbiAgICAgICAgdGhpcy5wYXJzZURPTShlbClcbiAgICAgIH0sIHRoaXMpXG5cbiAgICAgIC8vIEZpcmUgRXZlbnRzXG4gICAgICB0cmlnZ2VyKGRvY3VtZW50LFwicGpheDpjb21wbGV0ZSBwamF4OnN1Y2Nlc3NcIiwgb3B0aW9ucylcblxuICAgICAgb3B0aW9ucy5hbmFseXRpY3MoKVxuXG4gICAgICAvLyBTY3JvbGwgcGFnZSB0byB0b3Agb24gbmV3IHBhZ2UgbG9hZFxuICAgICAgaWYgKG9wdGlvbnMuc2Nyb2xsVG8gIT09IGZhbHNlKSB7XG4gICAgICAgIGlmIChvcHRpb25zLnNjcm9sbFRvLmxlbmd0aCA+IDEpIHtcbiAgICAgICAgICB3aW5kb3cuc2Nyb2xsVG8ob3B0aW9ucy5zY3JvbGxUb1swXSwgb3B0aW9ucy5zY3JvbGxUb1sxXSlcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICB3aW5kb3cuc2Nyb2xsVG8oMCwgb3B0aW9ucy5zY3JvbGxUbylcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0uYmluZCh0aGlzKSlcbiAgfVxufVxuXG5QamF4LmlzU3VwcG9ydGVkID0gcmVxdWlyZShcIi4vbGliL2lzLXN1cHBvcnRlZC5qc1wiKTtcblxuLy9hcmd1YWJseSBjb3VsZCBkbyBgaWYoIHJlcXVpcmUoXCIuL2xpYi9pcy1zdXBwb3J0ZWQuanNcIikoKSkge2AgYnV0IHRoYXQgbWlnaHQgYmUgYSBsaXR0bGUgdG8gc2ltcGxlXG5pZiAoUGpheC5pc1N1cHBvcnRlZCgpKSB7XG4gIG1vZHVsZS5leHBvcnRzID0gUGpheFxufVxuLy8gaWYgdGhlcmUgaXNu4oCZdCByZXF1aXJlZCBicm93c2VyIGZ1bmN0aW9ucywgcmV0dXJuaW5nIHN0dXBpZCBhcGlcbmVsc2Uge1xuICB2YXIgc3R1cGlkUGpheCA9IGZ1bmN0aW9uKCkge31cbiAgZm9yICh2YXIga2V5IGluIFBqYXgucHJvdG90eXBlKSB7XG4gICAgaWYgKFBqYXgucHJvdG90eXBlLmhhc093blByb3BlcnR5KGtleSkgJiYgdHlwZW9mIFBqYXgucHJvdG90eXBlW2tleV0gPT09IFwiZnVuY3Rpb25cIikge1xuICAgICAgc3R1cGlkUGpheFtrZXldID0gc3R1cGlkUGpheFxuICAgIH1cbiAgfVxuXG4gIG1vZHVsZS5leHBvcnRzID0gc3R1cGlkUGpheFxufVxuIiwibW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbihvYmopIHtcbiAgaWYgKG51bGwgPT09IG9iaiB8fCBcIm9iamVjdFwiICE9IHR5cGVvZiBvYmopIHtcbiAgICByZXR1cm4gb2JqXG4gIH1cbiAgdmFyIGNvcHkgPSBvYmouY29uc3RydWN0b3IoKVxuICBmb3IgKHZhciBhdHRyIGluIG9iaikge1xuICAgIGlmIChvYmouaGFzT3duUHJvcGVydHkoYXR0cikpIHtcbiAgICAgIGNvcHlbYXR0cl0gPSBvYmpbYXR0cl1cbiAgICB9XG4gIH1cbiAgcmV0dXJuIGNvcHlcbn1cbiIsIm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24oZWwpIHtcbiAgdmFyIHF1ZXJ5U2VsZWN0b3IgPSB0aGlzLm9wdGlvbnMubWFpblNjcmlwdEVsZW1lbnQ7XG4gIHZhciBjb2RlID0gKGVsLnRleHQgfHwgZWwudGV4dENvbnRlbnQgfHwgZWwuaW5uZXJIVE1MIHx8IFwiXCIpXG4gIHZhciBzcmMgPSAoZWwuc3JjIHx8IFwiXCIpO1xuICB2YXIgcGFyZW50ID0gZWwucGFyZW50Tm9kZSB8fCBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKHF1ZXJ5U2VsZWN0b3IpIHx8IGRvY3VtZW50LmRvY3VtZW50RWxlbWVudFxuICB2YXIgc2NyaXB0ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudChcInNjcmlwdFwiKVxuICB2YXIgcHJvbWlzZSA9IG51bGw7XG5cbiAgdGhpcy5sb2coXCJFdmFsdWF0aW5nIFNjcmlwdDogXCIsIGVsKTtcblxuICBpZiAoY29kZS5tYXRjaChcImRvY3VtZW50LndyaXRlXCIpKSB7XG4gICAgaWYgKGNvbnNvbGUgJiYgY29uc29sZS5sb2cpIHtcbiAgICAgIGNvbnNvbGUubG9nKFwiU2NyaXB0IGNvbnRhaW5zIGRvY3VtZW50LndyaXRlLiBDYW7igJl0IGJlIGV4ZWN1dGVkIGNvcnJlY3RseS4gQ29kZSBza2lwcGVkIFwiLCBlbClcbiAgICB9XG4gICAgcmV0dXJuIGZhbHNlXG4gIH1cblxuICBwcm9taXNlID0gbmV3IFByb21pc2UoZnVuY3Rpb24ocmVzb2x2ZSwgcmVqZWN0KXtcblxuICAgIHNjcmlwdC50eXBlID0gXCJ0ZXh0L2phdmFzY3JpcHRcIlxuICAgIGlmIChzcmMgIT0gXCJcIikge1xuICAgICAgc2NyaXB0LnNyYyA9IHNyYztcbiAgICAgIHNjcmlwdC5vbmxvYWQgPSByZXNvbHZlO1xuICAgICAgc2NyaXB0LmFzeW5jID0gdHJ1ZTsgLy8gZm9yY2UgYXN5bmNocm9ub3VzIGxvYWRpbmcgb2YgcGVyaXBoZXJhbCBqc1xuICAgIH1cblxuICAgIGlmIChjb2RlICE9IFwiXCIpIHtcbiAgICAgIHRyeSB7XG4gICAgICAgIHNjcmlwdC5hcHBlbmRDaGlsZChkb2N1bWVudC5jcmVhdGVUZXh0Tm9kZShjb2RlKSlcbiAgICAgIH1cbiAgICAgIGNhdGNoIChlKSB7XG4gICAgICAgIC8vIG9sZCBJRXMgaGF2ZSBmdW5reSBzY3JpcHQgbm9kZXNcbiAgICAgICAgc2NyaXB0LnRleHQgPSBjb2RlXG4gICAgICB9XG4gICAgICByZXNvbHZlKCk7XG4gICAgfVxuICB9KTtcblxuICB0aGlzLmxvZygnUGFyZW50RWxlbWVudCA9PiAnLCBwYXJlbnQgKTtcblxuICAvLyBleGVjdXRlXG4gIHBhcmVudC5hcHBlbmRDaGlsZChzY3JpcHQpO1xuICBwYXJlbnQucmVtb3ZlQ2hpbGQoc2NyaXB0KVxuICAvLyBhdm9pZCBwb2xsdXRpb24gb25seSBpbiBoZWFkIG9yIGJvZHkgdGFnc1xuICBpZiAoW1wiaGVhZFwiLFwiYm9keVwiXS5pbmRleE9mKCBwYXJlbnQudGFnTmFtZS50b0xvd2VyQ2FzZSgpKSA+IDApIHtcbiAgfVxuXG4gIHJldHVybiBwcm9taXNlO1xufVxuIiwidmFyIGZvckVhY2hFbHMgPSByZXF1aXJlKFwiLi4vZm9yZWFjaC1lbHNcIilcblxubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbihlbHMsIGV2ZW50cywgbGlzdGVuZXIsIHVzZUNhcHR1cmUpIHtcbiAgZXZlbnRzID0gKHR5cGVvZiBldmVudHMgPT09IFwic3RyaW5nXCIgPyBldmVudHMuc3BsaXQoXCIgXCIpIDogZXZlbnRzKVxuXG4gIGV2ZW50cy5mb3JFYWNoKGZ1bmN0aW9uKGUpIHtcbiAgICBmb3JFYWNoRWxzKGVscywgZnVuY3Rpb24oZWwpIHtcbiAgICAgIGVsLmFkZEV2ZW50TGlzdGVuZXIoZSwgbGlzdGVuZXIsIHVzZUNhcHR1cmUpXG4gICAgfSlcbiAgfSlcbn1cbiIsInZhciBmb3JFYWNoRWxzID0gcmVxdWlyZShcIi4uL2ZvcmVhY2gtZWxzXCIpXG5cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24oZWxzLCBldmVudHMsIG9wdHMpIHtcbiAgZXZlbnRzID0gKHR5cGVvZiBldmVudHMgPT09IFwic3RyaW5nXCIgPyBldmVudHMuc3BsaXQoXCIgXCIpIDogZXZlbnRzKVxuXG4gIGV2ZW50cy5mb3JFYWNoKGZ1bmN0aW9uKGUpIHtcbiAgICB2YXIgZXZlbnQgLy8gPSBuZXcgQ3VzdG9tRXZlbnQoZSkgLy8gZG9lc24ndCBldmVyeXdoZXJlIHlldFxuICAgIGV2ZW50ID0gZG9jdW1lbnQuY3JlYXRlRXZlbnQoXCJIVE1MRXZlbnRzXCIpXG4gICAgZXZlbnQuaW5pdEV2ZW50KGUsIHRydWUsIHRydWUpXG4gICAgZXZlbnQuZXZlbnROYW1lID0gZVxuICAgIGlmIChvcHRzKSB7XG4gICAgICBPYmplY3Qua2V5cyhvcHRzKS5mb3JFYWNoKGZ1bmN0aW9uKGtleSkge1xuICAgICAgICBldmVudFtrZXldID0gb3B0c1trZXldXG4gICAgICB9KVxuICAgIH1cblxuICAgIGZvckVhY2hFbHMoZWxzLCBmdW5jdGlvbihlbCkge1xuICAgICAgdmFyIGRvbUZpeCA9IGZhbHNlXG4gICAgICBpZiAoIWVsLnBhcmVudE5vZGUgJiYgZWwgIT09IGRvY3VtZW50ICYmIGVsICE9PSB3aW5kb3cpIHtcbiAgICAgICAgLy8gVEhBTktTIFlPVSBJRSAoOS8xMC8vMTEgY29uY2VybmVkKVxuICAgICAgICAvLyBkaXNwYXRjaEV2ZW50IGRvZXNuJ3Qgd29yayBpZiBlbGVtZW50IGlzIG5vdCBpbiB0aGUgZG9tXG4gICAgICAgIGRvbUZpeCA9IHRydWVcbiAgICAgICAgZG9jdW1lbnQuYm9keS5hcHBlbmRDaGlsZChlbClcbiAgICAgIH1cbiAgICAgIGVsLmRpc3BhdGNoRXZlbnQoZXZlbnQpXG4gICAgICBpZiAoZG9tRml4KSB7XG4gICAgICAgIGVsLnBhcmVudE5vZGUucmVtb3ZlQ2hpbGQoZWwpXG4gICAgICB9XG4gICAgfSlcbiAgfSlcbn1cbiIsInZhciBmb3JFYWNoRWxzID0gcmVxdWlyZShcIi4vZm9yZWFjaC1lbHNcIilcbnZhciBldmFsU2NyaXB0ID0gcmVxdWlyZShcIi4vZXZhbC1zY3JpcHRcIilcbi8vIEZpbmRzIGFuZCBleGVjdXRlcyBzY3JpcHRzICh1c2VkIGZvciBuZXdseSBhZGRlZCBlbGVtZW50cylcbi8vIE5lZWRlZCBzaW5jZSBpbm5lckhUTUwgZG9lcyBub3QgcnVuIHNjcmlwdHNcbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24oZWwpIHtcblxuICB0aGlzLmxvZyhcIkV4ZWN1dGluZyBzY3JpcHRzIGZvciBcIiwgZWwpO1xuXG4gIHZhciBsb2FkaW5nU2NyaXB0cyA9IFtdO1xuXG4gIGlmKGVsID09PSB1bmRlZmluZWQpIHJldHVybiBQcm9taXNlLnJlc29sdmUoKTtcblxuICBpZiAoZWwudGFnTmFtZS50b0xvd2VyQ2FzZSgpID09PSBcInNjcmlwdFwiKSB7XG4gICAgZXZhbFNjcmlwdC5jYWxsKHRoaXMsIGVsKTtcbiAgfVxuXG4gIGZvckVhY2hFbHMoZWwucXVlcnlTZWxlY3RvckFsbChcInNjcmlwdFwiKSwgZnVuY3Rpb24oc2NyaXB0KSB7XG4gICAgaWYgKCFzY3JpcHQudHlwZSB8fCBzY3JpcHQudHlwZS50b0xvd2VyQ2FzZSgpID09PSBcInRleHQvamF2YXNjcmlwdFwiKSB7XG4gICAgICAvLyBpZiAoc2NyaXB0LnBhcmVudE5vZGUpIHtcbiAgICAgIC8vICAgc2NyaXB0LnBhcmVudE5vZGUucmVtb3ZlQ2hpbGQoc2NyaXB0KVxuICAgICAgLy8gfVxuICAgICAgbG9hZGluZ1NjcmlwdHMucHVzaChldmFsU2NyaXB0LmNhbGwodGhpcywgc2NyaXB0KSk7XG4gICAgfVxuICB9LCB0aGlzKTtcblxuICByZXR1cm4gUHJvbWlzZS5hbGwobG9hZGluZ1NjcmlwdHMpO1xufVxuIiwiLyogZ2xvYmFsIEhUTUxDb2xsZWN0aW9uOiB0cnVlICovXG5cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24oZWxzLCBmbiwgY29udGV4dCkge1xuICBpZiAoZWxzIGluc3RhbmNlb2YgSFRNTENvbGxlY3Rpb24gfHwgZWxzIGluc3RhbmNlb2YgTm9kZUxpc3QgfHwgZWxzIGluc3RhbmNlb2YgQXJyYXkpIHtcbiAgICByZXR1cm4gQXJyYXkucHJvdG90eXBlLmZvckVhY2guY2FsbChlbHMsIGZuLCBjb250ZXh0KVxuICB9XG4gIC8vIGFzc3VtZSBzaW1wbGUgZG9tIGVsZW1lbnRcbiAgcmV0dXJuIGZuLmNhbGwoY29udGV4dCwgZWxzKVxufVxuIiwidmFyIGZvckVhY2hFbHMgPSByZXF1aXJlKFwiLi9mb3JlYWNoLWVsc1wiKVxuXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uKHNlbGVjdG9ycywgY2IsIGNvbnRleHQsIERPTWNvbnRleHQpIHtcbiAgRE9NY29udGV4dCA9IERPTWNvbnRleHQgfHwgZG9jdW1lbnRcbiAgc2VsZWN0b3JzLmZvckVhY2goZnVuY3Rpb24oc2VsZWN0b3IpIHtcbiAgICBmb3JFYWNoRWxzKERPTWNvbnRleHQucXVlcnlTZWxlY3RvckFsbChzZWxlY3RvciksIGNiLCBjb250ZXh0KVxuICB9KVxufVxuIiwibW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbigpIHtcbiAgLy8gQm9ycm93ZWQgd2hvbGVzYWxlIGZyb20gaHR0cHM6Ly9naXRodWIuY29tL2RlZnVua3QvanF1ZXJ5LXBqYXhcbiAgcmV0dXJuIHdpbmRvdy5oaXN0b3J5ICYmXG4gICAgd2luZG93Lmhpc3RvcnkucHVzaFN0YXRlICYmXG4gICAgd2luZG93Lmhpc3RvcnkucmVwbGFjZVN0YXRlICYmXG4gICAgLy8gcHVzaFN0YXRlIGlzbuKAmXQgcmVsaWFibGUgb24gaU9TIHVudGlsIDUuXG4gICAgIW5hdmlnYXRvci51c2VyQWdlbnQubWF0Y2goLygoaVBvZHxpUGhvbmV8aVBhZCkuK1xcYk9TXFxzK1sxLTRdXFxEfFdlYkFwcHNcXC8uK0NGTmV0d29yaykvKVxufVxuIiwiaWYgKCFGdW5jdGlvbi5wcm90b3R5cGUuYmluZCkge1xuICBGdW5jdGlvbi5wcm90b3R5cGUuYmluZCA9IGZ1bmN0aW9uKG9UaGlzKSB7XG4gICAgaWYgKHR5cGVvZiB0aGlzICE9PSBcImZ1bmN0aW9uXCIpIHtcbiAgICAgIC8vIGNsb3Nlc3QgdGhpbmcgcG9zc2libGUgdG8gdGhlIEVDTUFTY3JpcHQgNSBpbnRlcm5hbCBJc0NhbGxhYmxlIGZ1bmN0aW9uXG4gICAgICB0aHJvdyBuZXcgVHlwZUVycm9yKFwiRnVuY3Rpb24ucHJvdG90eXBlLmJpbmQgLSB3aGF0IGlzIHRyeWluZyB0byBiZSBib3VuZCBpcyBub3QgY2FsbGFibGVcIilcbiAgICB9XG5cbiAgICB2YXIgYUFyZ3MgPSBBcnJheS5wcm90b3R5cGUuc2xpY2UuY2FsbChhcmd1bWVudHMsIDEpXG4gICAgdmFyIHRoYXQgPSB0aGlzXG4gICAgdmFyIEZub29wID0gZnVuY3Rpb24oKSB7fVxuICAgIHZhciBmQm91bmQgPSBmdW5jdGlvbigpIHtcbiAgICAgIHJldHVybiB0aGF0LmFwcGx5KHRoaXMgaW5zdGFuY2VvZiBGbm9vcCAmJiBvVGhpcyA/IHRoaXMgOiBvVGhpcywgYUFyZ3MuY29uY2F0KEFycmF5LnByb3RvdHlwZS5zbGljZS5jYWxsKGFyZ3VtZW50cykpKVxuICAgIH1cblxuICAgIEZub29wLnByb3RvdHlwZSA9IHRoaXMucHJvdG90eXBlXG4gICAgZkJvdW5kLnByb3RvdHlwZSA9IG5ldyBGbm9vcCgpXG5cbiAgICByZXR1cm4gZkJvdW5kXG4gIH1cbn1cbiIsInJlcXVpcmUoXCIuLi9wb2x5ZmlsbHMvRnVuY3Rpb24ucHJvdG90eXBlLmJpbmRcIilcblxudmFyIG9uID0gcmVxdWlyZShcIi4uL2V2ZW50cy9vblwiKVxudmFyIGNsb25lID0gcmVxdWlyZShcIi4uL2Nsb25lXCIpXG5cbnZhciBhdHRyQ2xpY2sgPSBcImRhdGEtcGpheC1jbGljay1zdGF0ZVwiXG5cbnZhciBmb3JtQWN0aW9uID0gZnVuY3Rpb24oZWwsIGV2ZW50KXtcblxuICB0aGlzLm9wdGlvbnMucmVxdWVzdE9wdGlvbnMgPSB7XG4gICAgcmVxdWVzdFVybCA6IGVsLmdldEF0dHJpYnV0ZSgnYWN0aW9uJykgfHwgd2luZG93LmxvY2F0aW9uLmhyZWYsXG4gICAgcmVxdWVzdE1ldGhvZCA6IGVsLmdldEF0dHJpYnV0ZSgnbWV0aG9kJykgfHwgJ0dFVCcsXG4gIH1cblxuICAvL2NyZWF0ZSBhIHRlc3RhYmxlIHZpcnR1YWwgbGluayBvZiB0aGUgZm9ybSBhY3Rpb25cbiAgdmFyIHZpcnRMaW5rRWxlbWVudCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2EnKTtcbiAgdmlydExpbmtFbGVtZW50LnNldEF0dHJpYnV0ZSgnaHJlZicsIHRoaXMub3B0aW9ucy5yZXF1ZXN0T3B0aW9ucy5yZXF1ZXN0VXJsKTtcblxuICAvLyBJZ25vcmUgZXh0ZXJuYWwgbGlua3MuXG4gIGlmICh2aXJ0TGlua0VsZW1lbnQucHJvdG9jb2wgIT09IHdpbmRvdy5sb2NhdGlvbi5wcm90b2NvbCB8fCB2aXJ0TGlua0VsZW1lbnQuaG9zdCAhPT0gd2luZG93LmxvY2F0aW9uLmhvc3QpIHtcbiAgICBlbC5zZXRBdHRyaWJ1dGUoYXR0ckNsaWNrLCBcImV4dGVybmFsXCIpO1xuICAgIHJldHVyblxuICB9XG5cbiAgLy8gSWdub3JlIGNsaWNrIGlmIHdlIGFyZSBvbiBhbiBhbmNob3Igb24gdGhlIHNhbWUgcGFnZVxuICBpZiAodmlydExpbmtFbGVtZW50LnBhdGhuYW1lID09PSB3aW5kb3cubG9jYXRpb24ucGF0aG5hbWUgJiYgdmlydExpbmtFbGVtZW50Lmhhc2gubGVuZ3RoID4gMCkge1xuICAgIGVsLnNldEF0dHJpYnV0ZShhdHRyQ2xpY2ssIFwiYW5jaG9yLXByZXNlbnRcIik7XG4gICAgcmV0dXJuXG4gIH1cblxuICAvLyBJZ25vcmUgZW1wdHkgYW5jaG9yIFwiZm9vLmh0bWwjXCJcbiAgaWYgKHZpcnRMaW5rRWxlbWVudC5ocmVmID09PSB3aW5kb3cubG9jYXRpb24uaHJlZi5zcGxpdChcIiNcIilbMF0gKyBcIiNcIikge1xuICAgIGVsLnNldEF0dHJpYnV0ZShhdHRyQ2xpY2ssIFwiYW5jaG9yLWVtcHR5XCIpXG4gICAgcmV0dXJuXG4gIH1cblxuICAvLyBpZiBkZWNsYXJlZCBhcyBhIGZ1bGwgcmVsb2FkLCBqdXN0IG5vcm1hbGx5IHN1Ym1pdCB0aGUgZm9ybVxuICBpZiAoIHRoaXMub3B0aW9ucy5jdXJyZW50VXJsRnVsbFJlbG9hZCkge1xuICAgIGVsLnNldEF0dHJpYnV0ZShhdHRyQ2xpY2ssIFwicmVsb2FkXCIpO1xuICAgIHJldHVybjtcbiAgfVxuXG4gIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgdmFyIG5hbWVMaXN0ID0gW107XG4gIHZhciBwYXJhbU9iamVjdCA9IFtdO1xuICBmb3IodmFyIGVsZW1lbnRLZXkgaW4gZWwuZWxlbWVudHMpIHtcbiAgICB2YXIgZWxlbWVudCA9IGVsLmVsZW1lbnRzW2VsZW1lbnRLZXldO1xuICAgIGlmICghIWVsZW1lbnQubmFtZSAmJiBlbGVtZW50LmF0dHJpYnV0ZXMgIT09IHVuZGVmaW5lZCAmJiBlbGVtZW50LnRhZ05hbWUudG9Mb3dlckNhc2UoKSAhPT0gJ2J1dHRvbicpe1xuICAgICAgaWYgKFxuICAgICAgICAoZWxlbWVudC50eXBlICE9PSAnY2hlY2tib3gnICYmIGVsZW1lbnQudHlwZSAhPT0gJ3JhZGlvJykgfHwgZWxlbWVudC5jaGVja2VkXG4gICAgICApIHtcbiAgICAgICAgaWYobmFtZUxpc3QuaW5kZXhPZihlbGVtZW50Lm5hbWUpID09PSAtMSl7XG4gICAgICAgICAgbmFtZUxpc3QucHVzaChlbGVtZW50Lm5hbWUpO1xuICAgICAgICAgIHBhcmFtT2JqZWN0LnB1c2goeyBuYW1lOiBlbmNvZGVVUklDb21wb25lbnQoZWxlbWVudC5uYW1lKSwgdmFsdWU6IGVuY29kZVVSSUNvbXBvbmVudChlbGVtZW50LnZhbHVlKX0pO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfVxuICB9XG5cblxuXG4gIC8vQ3JlYXRpbmcgYSBnZXRTdHJpbmdcbiAgdmFyIHBhcmFtc1N0cmluZyA9IChwYXJhbU9iamVjdC5tYXAoZnVuY3Rpb24odmFsdWUpe3JldHVybiB2YWx1ZS5uYW1lK1wiPVwiK3ZhbHVlLnZhbHVlO30pKS5qb2luKCcmJyk7XG5cbiAgdGhpcy5vcHRpb25zLnJlcXVlc3RPcHRpb25zLnJlcXVlc3RQYXlsb2FkID0gcGFyYW1PYmplY3Q7XG4gIHRoaXMub3B0aW9ucy5yZXF1ZXN0T3B0aW9ucy5yZXF1ZXN0UGF5bG9hZFN0cmluZyA9IHBhcmFtc1N0cmluZztcblxuICBlbC5zZXRBdHRyaWJ1dGUoYXR0ckNsaWNrLCBcInN1Ym1pdFwiKTtcblxuICB0aGlzLmxvYWRVcmwodmlydExpbmtFbGVtZW50LmhyZWYsIGNsb25lKHRoaXMub3B0aW9ucykpXG5cbn07XG5cbnZhciBpc0RlZmF1bHRQcmV2ZW50ZWQgPSBmdW5jdGlvbihldmVudCkge1xuICByZXR1cm4gZXZlbnQuZGVmYXVsdFByZXZlbnRlZCB8fCBldmVudC5yZXR1cm5WYWx1ZSA9PT0gZmFsc2U7XG59O1xuXG5cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24oZWwpIHtcbiAgdmFyIHRoYXQgPSB0aGlzXG5cbiAgb24oZWwsIFwic3VibWl0XCIsIGZ1bmN0aW9uKGV2ZW50KSB7XG4gICAgaWYgKGlzRGVmYXVsdFByZXZlbnRlZChldmVudCkpIHtcbiAgICAgIHJldHVyblxuICAgIH1cblxuICAgIGZvcm1BY3Rpb24uY2FsbCh0aGF0LCBlbCwgZXZlbnQpXG4gIH0pXG5cbiAgb24oZWwsIFwia2V5dXBcIiwgZnVuY3Rpb24oZXZlbnQpIHtcbiAgICBpZiAoaXNEZWZhdWx0UHJldmVudGVkKGV2ZW50KSkge1xuICAgICAgcmV0dXJuXG4gICAgfVxuXG5cbiAgICBpZiAoZXZlbnQua2V5Q29kZSA9PSAxMykge1xuICAgICAgZm9ybUFjdGlvbi5jYWxsKHRoYXQsIGVsLCBldmVudClcbiAgICB9XG4gIH0uYmluZCh0aGlzKSlcbn1cbiIsInJlcXVpcmUoXCIuLi9wb2x5ZmlsbHMvRnVuY3Rpb24ucHJvdG90eXBlLmJpbmRcIilcblxudmFyIG9uID0gcmVxdWlyZShcIi4uL2V2ZW50cy9vblwiKVxudmFyIGNsb25lID0gcmVxdWlyZShcIi4uL2Nsb25lXCIpXG5cbnZhciBhdHRyQ2xpY2sgPSBcImRhdGEtcGpheC1jbGljay1zdGF0ZVwiXG52YXIgYXR0cktleSA9IFwiZGF0YS1wamF4LWtleXVwLXN0YXRlXCJcblxudmFyIGxpbmtBY3Rpb24gPSBmdW5jdGlvbihlbCwgZXZlbnQpIHtcbiAgLy8gRG9u4oCZdCBicmVhayBicm93c2VyIHNwZWNpYWwgYmVoYXZpb3Igb24gbGlua3MgKGxpa2UgcGFnZSBpbiBuZXcgd2luZG93KVxuICBpZiAoZXZlbnQud2hpY2ggPiAxIHx8IGV2ZW50Lm1ldGFLZXkgfHwgZXZlbnQuY3RybEtleSB8fCBldmVudC5zaGlmdEtleSB8fCBldmVudC5hbHRLZXkpIHtcbiAgICBlbC5zZXRBdHRyaWJ1dGUoYXR0ckNsaWNrLCBcIm1vZGlmaWVyXCIpXG4gICAgcmV0dXJuXG4gIH1cblxuICAvLyB3ZSBkbyB0ZXN0IG9uIGhyZWYgbm93IHRvIHByZXZlbnQgdW5leHBlY3RlZCBiZWhhdmlvciBpZiBmb3Igc29tZSByZWFzb25cbiAgLy8gdXNlciBoYXZlIGhyZWYgdGhhdCBjYW4gYmUgZHluYW1pY2FsbHkgdXBkYXRlZFxuXG4gIC8vIElnbm9yZSBleHRlcm5hbCBsaW5rcy5cbiAgaWYgKGVsLnByb3RvY29sICE9PSB3aW5kb3cubG9jYXRpb24ucHJvdG9jb2wgfHwgZWwuaG9zdCAhPT0gd2luZG93LmxvY2F0aW9uLmhvc3QpIHtcbiAgICBlbC5zZXRBdHRyaWJ1dGUoYXR0ckNsaWNrLCBcImV4dGVybmFsXCIpXG4gICAgcmV0dXJuXG4gIH1cblxuICAvLyBJZ25vcmUgY2xpY2sgaWYgd2UgYXJlIG9uIGFuIGFuY2hvciBvbiB0aGUgc2FtZSBwYWdlXG4gIGlmIChlbC5wYXRobmFtZSA9PT0gd2luZG93LmxvY2F0aW9uLnBhdGhuYW1lICYmIGVsLmhhc2gubGVuZ3RoID4gMCkge1xuICAgIGVsLnNldEF0dHJpYnV0ZShhdHRyQ2xpY2ssIFwiYW5jaG9yLXByZXNlbnRcIilcbiAgICByZXR1cm5cbiAgfVxuXG4gIC8vIElnbm9yZSBhbmNob3JzIG9uIHRoZSBzYW1lIHBhZ2UgKGtlZXAgbmF0aXZlIGJlaGF2aW9yKVxuICBpZiAoZWwuaGFzaCAmJiBlbC5ocmVmLnJlcGxhY2UoZWwuaGFzaCwgXCJcIikgPT09IHdpbmRvdy5sb2NhdGlvbi5ocmVmLnJlcGxhY2UobG9jYXRpb24uaGFzaCwgXCJcIikpIHtcbiAgICBlbC5zZXRBdHRyaWJ1dGUoYXR0ckNsaWNrLCBcImFuY2hvclwiKVxuICAgIHJldHVyblxuICB9XG5cbiAgLy8gSWdub3JlIGVtcHR5IGFuY2hvciBcImZvby5odG1sI1wiXG4gIGlmIChlbC5ocmVmID09PSB3aW5kb3cubG9jYXRpb24uaHJlZi5zcGxpdChcIiNcIilbMF0gKyBcIiNcIikge1xuICAgIGVsLnNldEF0dHJpYnV0ZShhdHRyQ2xpY2ssIFwiYW5jaG9yLWVtcHR5XCIpXG4gICAgcmV0dXJuXG4gIH1cblxuICBldmVudC5wcmV2ZW50RGVmYXVsdCgpXG5cbiAgLy8gZG9u4oCZdCBkbyBcIm5vdGhpbmdcIiBpZiB1c2VyIHRyeSB0byByZWxvYWQgdGhlIHBhZ2UgYnkgY2xpY2tpbmcgdGhlIHNhbWUgbGluayB0d2ljZVxuICBpZiAoXG4gICAgdGhpcy5vcHRpb25zLmN1cnJlbnRVcmxGdWxsUmVsb2FkICYmXG4gICAgZWwuaHJlZiA9PT0gd2luZG93LmxvY2F0aW9uLmhyZWYuc3BsaXQoXCIjXCIpWzBdXG4gICkge1xuICAgIGVsLnNldEF0dHJpYnV0ZShhdHRyQ2xpY2ssIFwicmVsb2FkXCIpXG4gICAgdGhpcy5yZWxvYWQoKVxuICAgIHJldHVyblxuICB9XG4gIHRoaXMub3B0aW9ucy5yZXF1ZXN0T3B0aW9ucyA9IHRoaXMub3B0aW9ucy5yZXF1ZXN0T3B0aW9ucyB8fCB7fTtcbiAgZWwuc2V0QXR0cmlidXRlKGF0dHJDbGljaywgXCJsb2FkXCIpXG4gIHRoaXMubG9hZFVybChlbC5ocmVmLCBjbG9uZSh0aGlzLm9wdGlvbnMpKVxufVxuXG52YXIgaXNEZWZhdWx0UHJldmVudGVkID0gZnVuY3Rpb24oZXZlbnQpIHtcbiAgcmV0dXJuIGV2ZW50LmRlZmF1bHRQcmV2ZW50ZWQgfHwgZXZlbnQucmV0dXJuVmFsdWUgPT09IGZhbHNlO1xufVxuXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uKGVsKSB7XG4gIHZhciB0aGF0ID0gdGhpc1xuXG4gIG9uKGVsLCBcImNsaWNrXCIsIGZ1bmN0aW9uKGV2ZW50KSB7XG4gICAgaWYgKGlzRGVmYXVsdFByZXZlbnRlZChldmVudCkpIHtcbiAgICAgIHJldHVyblxuICAgIH1cblxuICAgIGxpbmtBY3Rpb24uY2FsbCh0aGF0LCBlbCwgZXZlbnQpXG4gIH0pXG5cbiAgb24oZWwsIFwia2V5dXBcIiwgZnVuY3Rpb24oZXZlbnQpIHtcbiAgICBpZiAoaXNEZWZhdWx0UHJldmVudGVkKGV2ZW50KSkge1xuICAgICAgcmV0dXJuXG4gICAgfVxuXG4gICAgLy8gRG9u4oCZdCBicmVhayBicm93c2VyIHNwZWNpYWwgYmVoYXZpb3Igb24gbGlua3MgKGxpa2UgcGFnZSBpbiBuZXcgd2luZG93KVxuICAgIGlmIChldmVudC53aGljaCA+IDEgfHwgZXZlbnQubWV0YUtleSB8fCBldmVudC5jdHJsS2V5IHx8IGV2ZW50LnNoaWZ0S2V5IHx8IGV2ZW50LmFsdEtleSkge1xuICAgICAgZWwuc2V0QXR0cmlidXRlKGF0dHJLZXksIFwibW9kaWZpZXJcIilcbiAgICAgIHJldHVyblxuICAgIH1cblxuICAgIGlmIChldmVudC5rZXlDb2RlID09IDEzKSB7XG4gICAgICBsaW5rQWN0aW9uLmNhbGwodGhhdCwgZWwsIGV2ZW50KVxuICAgIH1cbiAgfS5iaW5kKHRoaXMpKVxufVxuIiwibW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbihlbCkge1xuICByZXR1cm4gZWwucXVlcnlTZWxlY3RvckFsbCh0aGlzLm9wdGlvbnMuZWxlbWVudHMpXG59XG4iLCJtb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uKCkge1xuICBpZiAoKHRoaXMub3B0aW9ucy5kZWJ1ZyAmJiBjb25zb2xlKSkge1xuICAgIGlmICh0eXBlb2YgY29uc29sZS5sb2cgPT09IFwiZnVuY3Rpb25cIikge1xuICAgICAgY29uc29sZS5sb2cuYXBwbHkoY29uc29sZSwgWydQSkFYIC0+Jyxhcmd1bWVudHNdKTtcbiAgICB9XG4gICAgLy8gaWUgaXMgd2VpcmRcbiAgICBlbHNlIGlmIChjb25zb2xlLmxvZykge1xuICAgICAgY29uc29sZS5sb2coWydQSkFYIC0+Jyxhcmd1bWVudHNdKTtcbiAgICB9XG4gIH1cbn1cbiIsInZhciBmb3JFYWNoRWxzID0gcmVxdWlyZShcIi4uL2ZvcmVhY2gtZWxzXCIpXG5cbnZhciBwYXJzZUVsZW1lbnQgPSByZXF1aXJlKFwiLi9wYXJzZS1lbGVtZW50XCIpXG5cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24oZWwpIHtcbiAgZm9yRWFjaEVscyh0aGlzLmdldEVsZW1lbnRzKGVsKSwgcGFyc2VFbGVtZW50LCB0aGlzKVxufVxuIiwibW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbihlbCkge1xuICBzd2l0Y2ggKGVsLnRhZ05hbWUudG9Mb3dlckNhc2UoKSkge1xuICBjYXNlIFwiYVwiOlxuICAgIC8vIG9ubHkgYXR0YWNoIGxpbmsgaWYgZWwgZG9lcyBub3QgYWxyZWFkeSBoYXZlIGxpbmsgYXR0YWNoZWRcbiAgICBpZiAoIWVsLmhhc0F0dHJpYnV0ZSgnZGF0YS1wamF4LWNsaWNrLXN0YXRlJykpIHtcbiAgICAgIHRoaXMuYXR0YWNoTGluayhlbClcbiAgICB9XG4gICAgYnJlYWtcblxuICAgIGNhc2UgXCJmb3JtXCI6XG4gICAgICAvLyBvbmx5IGF0dGFjaCBsaW5rIGlmIGVsIGRvZXMgbm90IGFscmVhZHkgaGF2ZSBsaW5rIGF0dGFjaGVkXG4gICAgICBpZiAoIWVsLmhhc0F0dHJpYnV0ZSgnZGF0YS1wamF4LWNsaWNrLXN0YXRlJykpIHtcbiAgICAgICAgdGhpcy5hdHRhY2hGb3JtKGVsKVxuICAgICAgfVxuICAgIGJyZWFrXG5cbiAgZGVmYXVsdDpcbiAgICB0aHJvdyBcIlBqYXggY2FuIG9ubHkgYmUgYXBwbGllZCBvbiA8YT4gb3IgPGZvcm0+IHN1Ym1pdFwiXG4gIH1cbn1cbiIsIi8qIGdsb2JhbCBfZ2FxOiB0cnVlLCBnYTogdHJ1ZSAqL1xuXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uKG9wdGlvbnMpe1xuICB0aGlzLm9wdGlvbnMgPSBvcHRpb25zXG4gIHRoaXMub3B0aW9ucy5lbGVtZW50cyA9IHRoaXMub3B0aW9ucy5lbGVtZW50cyB8fCBcImFbaHJlZl0sIGZvcm1bYWN0aW9uXVwiLFxuICB0aGlzLm9wdGlvbnMucmVSZW5kZXJDU1MgPSB0aGlzLm9wdGlvbnMucmVSZW5kZXJDU1MgfHwgZmFsc2UsXG4gIHRoaXMub3B0aW9ucy5mb3JjZVJlZGlyZWN0T25GYWlsID0gdGhpcy5vcHRpb25zLmZvcmNlUmVkaXJlY3RPbkZhaWwgfHwgZmFsc2UsXG4gIHRoaXMub3B0aW9ucy5zY3JpcHRsb2FkdGltZW91dCA9IHRoaXMub3B0aW9ucy5zY3JpcHRsb2FkdGltZW91dCB8fCAxMDAwLFxuICB0aGlzLm9wdGlvbnMubWFpblNjcmlwdEVsZW1lbnQgPSB0aGlzLm9wdGlvbnMubWFpblNjcmlwdEVsZW1lbnQgfHwgXCJoZWFkXCJcbiAgdGhpcy5vcHRpb25zLnNlbGVjdG9ycyA9IHRoaXMub3B0aW9ucy5zZWxlY3RvcnMgfHwgW1widGl0bGVcIiwgXCIuanMtUGpheFwiXVxuICB0aGlzLm9wdGlvbnMuc3dpdGNoZXMgPSB0aGlzLm9wdGlvbnMuc3dpdGNoZXMgfHwge31cbiAgdGhpcy5vcHRpb25zLnN3aXRjaGVzT3B0aW9ucyA9IHRoaXMub3B0aW9ucy5zd2l0Y2hlc09wdGlvbnMgfHwge31cbiAgdGhpcy5vcHRpb25zLmhpc3RvcnkgPSB0aGlzLm9wdGlvbnMuaGlzdG9yeSB8fCB0cnVlXG4gIHRoaXMub3B0aW9ucy5hbmFseXRpY3MgPSB0aGlzLm9wdGlvbnMuYW5hbHl0aWNzIHx8IGZ1bmN0aW9uKCkge1xuICAgIC8vIG9wdGlvbnMuYmFja3dhcmQgb3Igb3B0aW9ucy5mb3dhcmQgY2FuIGJlIHRydWUgb3IgdW5kZWZpbmVkXG4gICAgLy8gYnkgZGVmYXVsdCwgd2UgZG8gdHJhY2sgYmFjay9mb3dhcmQgaGl0XG4gICAgLy8gaHR0cHM6Ly9wcm9kdWN0Zm9ydW1zLmdvb2dsZS5jb20vZm9ydW0vIyF0b3BpYy9hbmFseXRpY3MvV1Z3TURqTGhYWWtcbiAgICBpZiAod2luZG93Ll9nYXEpIHtcbiAgICAgIF9nYXEucHVzaChbXCJfdHJhY2tQYWdldmlld1wiXSlcbiAgICB9XG4gICAgaWYgKHdpbmRvdy5nYSkge1xuICAgICAgZ2EoXCJzZW5kXCIsIFwicGFnZXZpZXdcIiwge3BhZ2U6IGxvY2F0aW9uLnBhdGhuYW1lLCB0aXRsZTogZG9jdW1lbnQudGl0bGV9KVxuICAgIH1cbiAgfVxuICB0aGlzLm9wdGlvbnMuc2Nyb2xsVG8gPSAodHlwZW9mIHRoaXMub3B0aW9ucy5zY3JvbGxUbyA9PT0gJ3VuZGVmaW5lZCcpID8gMCA6IHRoaXMub3B0aW9ucy5zY3JvbGxUbztcbiAgdGhpcy5vcHRpb25zLmNhY2hlQnVzdCA9ICh0eXBlb2YgdGhpcy5vcHRpb25zLmNhY2hlQnVzdCA9PT0gJ3VuZGVmaW5lZCcpID8gdHJ1ZSA6IHRoaXMub3B0aW9ucy5jYWNoZUJ1c3RcbiAgdGhpcy5vcHRpb25zLmRlYnVnID0gdGhpcy5vcHRpb25zLmRlYnVnIHx8IGZhbHNlXG5cbiAgLy8gd2UgY2Fu4oCZdCByZXBsYWNlIGJvZHkub3V0ZXJIVE1MIG9yIGhlYWQub3V0ZXJIVE1MXG4gIC8vIGl0IGNyZWF0ZSBhIGJ1ZyB3aGVyZSBuZXcgYm9keSBvciBuZXcgaGVhZCBhcmUgY3JlYXRlZCBpbiB0aGUgZG9tXG4gIC8vIGlmIHlvdSBzZXQgaGVhZC5vdXRlckhUTUwsIGEgbmV3IGJvZHkgdGFnIGlzIGFwcGVuZGVkLCBzbyB0aGUgZG9tIGdldCAyIGJvZHlcbiAgLy8gJiBpdCBicmVhayB0aGUgc3dpdGNoRmFsbGJhY2sgd2hpY2ggcmVwbGFjZSBoZWFkICYgYm9keVxuICBpZiAoIXRoaXMub3B0aW9ucy5zd2l0Y2hlcy5oZWFkKSB7XG4gICAgdGhpcy5vcHRpb25zLnN3aXRjaGVzLmhlYWQgPSB0aGlzLnN3aXRjaEVsZW1lbnRzQWx0XG4gIH1cbiAgaWYgKCF0aGlzLm9wdGlvbnMuc3dpdGNoZXMuYm9keSkge1xuICAgIHRoaXMub3B0aW9ucy5zd2l0Y2hlcy5ib2R5ID0gdGhpcy5zd2l0Y2hFbGVtZW50c0FsdFxuICB9XG4gIGlmICh0eXBlb2Ygb3B0aW9ucy5hbmFseXRpY3MgIT09IFwiZnVuY3Rpb25cIikge1xuICAgIG9wdGlvbnMuYW5hbHl0aWNzID0gZnVuY3Rpb24oKSB7fVxuICB9XG59XG4iLCJtb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uKGVsKSB7XG4gIHRoaXMucGFyc2VET00oZWwgfHwgZG9jdW1lbnQpXG59XG4iLCJtb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uKCkge1xuICB3aW5kb3cubG9jYXRpb24ucmVsb2FkKClcbn1cbiIsIm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24obG9jYXRpb24sIG9wdGlvbnMsIGNhbGxiYWNrKSB7XG4gIG9wdGlvbnMgPSBvcHRpb25zIHx8IHt9O1xuICB2YXIgcmVxdWVzdE1ldGhvZCA9IG9wdGlvbnMucmVxdWVzdE1ldGhvZCB8fCBcIkdFVFwiO1xuICB2YXIgcmVxdWVzdFBheWxvYWQgPSBvcHRpb25zLnJlcXVlc3RQYXlsb2FkU3RyaW5nIHx8IG51bGw7XG4gIHZhciByZXF1ZXN0ID0gbmV3IFhNTEh0dHBSZXF1ZXN0KClcblxuICByZXF1ZXN0Lm9ucmVhZHlzdGF0ZWNoYW5nZSA9IGZ1bmN0aW9uKCkge1xuICAgIGlmIChyZXF1ZXN0LnJlYWR5U3RhdGUgPT09IDQpIHtcbiAgICAgIGlmIChyZXF1ZXN0LnN0YXR1cyA9PT0gMjAwKSB7XG4gICAgICAgIGNhbGxiYWNrKHJlcXVlc3QucmVzcG9uc2VUZXh0LCByZXF1ZXN0KVxuICAgICAgfVxuICAgICAgZWxzZSB7XG4gICAgICAgIGNhbGxiYWNrKG51bGwsIHJlcXVlc3QpXG4gICAgICB9XG4gICAgfVxuICB9XG5cbiAgLy8gQWRkIGEgdGltZXN0YW1wIGFzIHBhcnQgb2YgdGhlIHF1ZXJ5IHN0cmluZyBpZiBjYWNoZSBidXN0aW5nIGlzIGVuYWJsZWRcbiAgaWYgKHRoaXMub3B0aW9ucy5jYWNoZUJ1c3QpIHtcbiAgICBsb2NhdGlvbiArPSAoIS9bPyZdLy50ZXN0KGxvY2F0aW9uKSA/IFwiP1wiIDogXCImXCIpICsgbmV3IERhdGUoKS5nZXRUaW1lKClcbiAgfVxuXG4gIHJlcXVlc3Qub3BlbihyZXF1ZXN0TWV0aG9kLnRvVXBwZXJDYXNlKCksIGxvY2F0aW9uLCB0cnVlKVxuICByZXF1ZXN0LnNldFJlcXVlc3RIZWFkZXIoXCJYLVJlcXVlc3RlZC1XaXRoXCIsIFwiWE1MSHR0cFJlcXVlc3RcIilcblxuICAvLyBBZGQgdGhlIHJlcXVlc3QgcGF5bG9hZCBpZiBhdmFpbGFibGVcbiAgaWYgKG9wdGlvbnMucmVxdWVzdFBheWxvYWRTdHJpbmcgIT0gdW5kZWZpbmVkICYmIG9wdGlvbnMucmVxdWVzdFBheWxvYWRTdHJpbmcgIT0gXCJcIikge1xuICAgIC8vIFNlbmQgdGhlIHByb3BlciBoZWFkZXIgaW5mb3JtYXRpb24gYWxvbmcgd2l0aCB0aGUgcmVxdWVzdFxuICAgIHJlcXVlc3Quc2V0UmVxdWVzdEhlYWRlcihcIkNvbnRlbnQtdHlwZVwiLCBcImFwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZFwiKTtcbiAgfVxuXG4gIHJlcXVlc3Quc2VuZChyZXF1ZXN0UGF5bG9hZClcblxuICByZXR1cm4gcmVxdWVzdFxufVxuIiwidmFyIGZvckVhY2hFbHMgPSByZXF1aXJlKFwiLi9mb3JlYWNoLWVsc1wiKVxuXG52YXIgZGVmYXVsdFN3aXRjaGVzID0gcmVxdWlyZShcIi4vc3dpdGNoZXNcIilcblxubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbihzd2l0Y2hlcywgc3dpdGNoZXNPcHRpb25zLCBzZWxlY3RvcnMsIGZyb21FbCwgdG9FbCwgb3B0aW9ucykge1xuICBzZWxlY3RvcnMuZm9yRWFjaChmdW5jdGlvbihzZWxlY3Rvcikge1xuICAgIHZhciBuZXdFbHMgPSBmcm9tRWwucXVlcnlTZWxlY3RvckFsbChzZWxlY3RvcilcbiAgICB2YXIgb2xkRWxzID0gdG9FbC5xdWVyeVNlbGVjdG9yQWxsKHNlbGVjdG9yKVxuICAgIGlmICh0aGlzLmxvZykge1xuICAgICAgdGhpcy5sb2coXCJQamF4IHN3aXRjaFwiLCBzZWxlY3RvciwgbmV3RWxzLCBvbGRFbHMpXG4gICAgfVxuICAgIGlmIChuZXdFbHMubGVuZ3RoICE9PSBvbGRFbHMubGVuZ3RoKSB7XG4gICAgICAvLyBmb3JFYWNoRWxzKG5ld0VscywgZnVuY3Rpb24oZWwpIHtcbiAgICAgIC8vICAgdGhpcy5sb2coXCJuZXdFbFwiLCBlbCwgZWwub3V0ZXJIVE1MKVxuICAgICAgLy8gfSwgdGhpcylcbiAgICAgIC8vIGZvckVhY2hFbHMob2xkRWxzLCBmdW5jdGlvbihlbCkge1xuICAgICAgLy8gICB0aGlzLmxvZyhcIm9sZEVsXCIsIGVsLCBlbC5vdXRlckhUTUwpXG4gICAgICAvLyB9LCB0aGlzKVxuICAgICAgdGhyb3cgXCJET00gZG9lc27igJl0IGxvb2sgdGhlIHNhbWUgb24gbmV3IGxvYWRlZCBwYWdlOiDigJlcIiArIHNlbGVjdG9yICsgXCLigJkgLSBuZXcgXCIgKyBuZXdFbHMubGVuZ3RoICsgXCIsIG9sZCBcIiArIG9sZEVscy5sZW5ndGhcbiAgICB9XG5cbiAgICBmb3JFYWNoRWxzKG5ld0VscywgZnVuY3Rpb24obmV3RWwsIGkpIHtcbiAgICAgIHZhciBvbGRFbCA9IG9sZEVsc1tpXVxuICAgICAgaWYgKHRoaXMubG9nKSB7XG4gICAgICAgIHRoaXMubG9nKFwibmV3RWxcIiwgbmV3RWwsIFwib2xkRWxcIiwgb2xkRWwpXG4gICAgICB9XG4gICAgICBpZiAoc3dpdGNoZXNbc2VsZWN0b3JdKSB7XG4gICAgICAgIHN3aXRjaGVzW3NlbGVjdG9yXS5iaW5kKHRoaXMpKG9sZEVsLCBuZXdFbCwgb3B0aW9ucywgc3dpdGNoZXNPcHRpb25zW3NlbGVjdG9yXSlcbiAgICAgIH1cbiAgICAgIGVsc2Uge1xuICAgICAgICBkZWZhdWx0U3dpdGNoZXMub3V0ZXJIVE1MLmJpbmQodGhpcykob2xkRWwsIG5ld0VsLCBvcHRpb25zKVxuICAgICAgfVxuICAgIH0sIHRoaXMpXG4gIH0sIHRoaXMpXG59XG4iLCJ2YXIgb24gPSByZXF1aXJlKFwiLi9ldmVudHMvb24uanNcIilcbi8vIHZhciBvZmYgPSByZXF1aXJlKFwiLi9saWIvZXZlbnRzL29uLmpzXCIpXG4vLyB2YXIgdHJpZ2dlciA9IHJlcXVpcmUoXCIuL2xpYi9ldmVudHMvdHJpZ2dlci5qc1wiKVxuXG5cbm1vZHVsZS5leHBvcnRzID0ge1xuICBvdXRlckhUTUw6IGZ1bmN0aW9uKG9sZEVsLCBuZXdFbCkge1xuICAgIG9sZEVsLm91dGVySFRNTCA9IG5ld0VsLm91dGVySFRNTFxuICAgIHRoaXMub25Td2l0Y2goKVxuICB9LFxuXG4gIGlubmVySFRNTDogZnVuY3Rpb24ob2xkRWwsIG5ld0VsKSB7XG4gICAgb2xkRWwuaW5uZXJIVE1MID0gbmV3RWwuaW5uZXJIVE1MXG4gICAgb2xkRWwuY2xhc3NOYW1lID0gbmV3RWwuY2xhc3NOYW1lXG4gICAgdGhpcy5vblN3aXRjaCgpXG4gIH0sXG5cbiAgc2lkZUJ5U2lkZTogZnVuY3Rpb24ob2xkRWwsIG5ld0VsLCBvcHRpb25zLCBzd2l0Y2hPcHRpb25zKSB7XG4gICAgdmFyIGZvckVhY2ggPSBBcnJheS5wcm90b3R5cGUuZm9yRWFjaFxuICAgIHZhciBlbHNUb1JlbW92ZSA9IFtdXG4gICAgdmFyIGVsc1RvQWRkID0gW11cbiAgICB2YXIgZnJhZ1RvQXBwZW5kID0gZG9jdW1lbnQuY3JlYXRlRG9jdW1lbnRGcmFnbWVudCgpXG4gICAgLy8gaGVpZ2h0IHRyYW5zaXRpb24gYXJlIHNoaXR0eSBvbiBzYWZhcmlcbiAgICAvLyBzbyBjb21tZW50ZWQgZm9yIG5vdyAodW50aWwgSSBmb3VuZCBzb21ldGhpbmcgPylcbiAgICAvLyB2YXIgcmVsZXZhbnRIZWlnaHQgPSAwXG4gICAgdmFyIGFuaW1hdGlvbkV2ZW50TmFtZXMgPSBcImFuaW1hdGlvbmVuZCB3ZWJraXRBbmltYXRpb25FbmQgTVNBbmltYXRpb25FbmQgb2FuaW1hdGlvbmVuZFwiXG4gICAgdmFyIGFuaW1hdGVkRWxzTnVtYmVyID0gMFxuICAgIHZhciBzZXh5QW5pbWF0aW9uRW5kID0gZnVuY3Rpb24oZSkge1xuICAgICAgICAgIGlmIChlLnRhcmdldCAhPSBlLmN1cnJlbnRUYXJnZXQpIHtcbiAgICAgICAgICAgIC8vIGVuZCB0cmlnZ2VyZWQgYnkgYW4gYW5pbWF0aW9uIG9uIGEgY2hpbGRcbiAgICAgICAgICAgIHJldHVyblxuICAgICAgICAgIH1cblxuICAgICAgICAgIGFuaW1hdGVkRWxzTnVtYmVyLS1cbiAgICAgICAgICBpZiAoYW5pbWF0ZWRFbHNOdW1iZXIgPD0gMCAmJiBlbHNUb1JlbW92ZSkge1xuICAgICAgICAgICAgZWxzVG9SZW1vdmUuZm9yRWFjaChmdW5jdGlvbihlbCkge1xuICAgICAgICAgICAgICAvLyBicm93c2luZyBxdWlja2x5IGNhbiBtYWtlIHRoZSBlbFxuICAgICAgICAgICAgICAvLyBhbHJlYWR5IHJlbW92ZWQgYnkgbGFzdCBwYWdlIHVwZGF0ZSA/XG4gICAgICAgICAgICAgIGlmIChlbC5wYXJlbnROb2RlKSB7XG4gICAgICAgICAgICAgICAgZWwucGFyZW50Tm9kZS5yZW1vdmVDaGlsZChlbClcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSlcblxuICAgICAgICAgICAgZWxzVG9BZGQuZm9yRWFjaChmdW5jdGlvbihlbCkge1xuICAgICAgICAgICAgICBlbC5jbGFzc05hbWUgPSBlbC5jbGFzc05hbWUucmVwbGFjZShlbC5nZXRBdHRyaWJ1dGUoXCJkYXRhLXBqYXgtY2xhc3Nlc1wiKSwgXCJcIilcbiAgICAgICAgICAgICAgZWwucmVtb3ZlQXR0cmlidXRlKFwiZGF0YS1wamF4LWNsYXNzZXNcIilcbiAgICAgICAgICAgICAgLy8gUGpheC5vZmYoZWwsIGFuaW1hdGlvbkV2ZW50TmFtZXMsIHNleHlBbmltYXRpb25FbmQsIHRydWUpXG4gICAgICAgICAgICB9KVxuXG4gICAgICAgICAgICBlbHNUb0FkZCA9IG51bGwgLy8gZnJlZSBtZW1vcnlcbiAgICAgICAgICAgIGVsc1RvUmVtb3ZlID0gbnVsbCAvLyBmcmVlIG1lbW9yeVxuXG4gICAgICAgICAgICAvLyBhc3N1bWUgdGhlIGhlaWdodCBpcyBub3cgdXNlbGVzcyAoYXZvaWQgYnVnIHNpbmNlIHRoZXJlIGlzIG92ZXJmbG93IGhpZGRlbiBvbiB0aGUgcGFyZW50KVxuICAgICAgICAgICAgLy8gb2xkRWwuc3R5bGUuaGVpZ2h0ID0gXCJhdXRvXCJcblxuICAgICAgICAgICAgLy8gdGhpcyBpcyB0byB0cmlnZ2VyIHNvbWUgcmVwYWludCAoZXhhbXBsZTogcGljdHVyZWZpbGwpXG4gICAgICAgICAgICB0aGlzLm9uU3dpdGNoKClcbiAgICAgICAgICAgIC8vIFBqYXgudHJpZ2dlcih3aW5kb3csIFwic2Nyb2xsXCIpXG4gICAgICAgICAgfVxuICAgICAgICB9LmJpbmQodGhpcylcblxuICAgIC8vIEZvcmNlIGhlaWdodCB0byBiZSBhYmxlIHRvIHRyaWdnZXIgY3NzIGFuaW1hdGlvblxuICAgIC8vIGhlcmUgd2UgZ2V0IHRoZSByZWxldmFudCBoZWlnaHRcbiAgICAvLyBvbGRFbC5wYXJlbnROb2RlLmFwcGVuZENoaWxkKG5ld0VsKVxuICAgIC8vIHJlbGV2YW50SGVpZ2h0ID0gbmV3RWwuZ2V0Qm91bmRpbmdDbGllbnRSZWN0KCkuaGVpZ2h0XG4gICAgLy8gb2xkRWwucGFyZW50Tm9kZS5yZW1vdmVDaGlsZChuZXdFbClcbiAgICAvLyBvbGRFbC5zdHlsZS5oZWlnaHQgPSBvbGRFbC5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKS5oZWlnaHQgKyBcInB4XCJcblxuICAgIHN3aXRjaE9wdGlvbnMgPSBzd2l0Y2hPcHRpb25zIHx8IHt9XG5cbiAgICBmb3JFYWNoLmNhbGwob2xkRWwuY2hpbGROb2RlcywgZnVuY3Rpb24oZWwpIHtcbiAgICAgIGVsc1RvUmVtb3ZlLnB1c2goZWwpXG4gICAgICBpZiAoZWwuY2xhc3NMaXN0ICYmICFlbC5jbGFzc0xpc3QuY29udGFpbnMoXCJqcy1QamF4LXJlbW92ZVwiKSkge1xuICAgICAgICAvLyBmb3IgZmFzdCBzd2l0Y2gsIGNsZWFuIGVsZW1lbnQgdGhhdCBqdXN0IGhhdmUgYmVlbiBhZGRlZCwgJiBub3QgY2xlYW5lZCB5ZXQuXG4gICAgICAgIGlmIChlbC5oYXNBdHRyaWJ1dGUoXCJkYXRhLXBqYXgtY2xhc3Nlc1wiKSkge1xuICAgICAgICAgIGVsLmNsYXNzTmFtZSA9IGVsLmNsYXNzTmFtZS5yZXBsYWNlKGVsLmdldEF0dHJpYnV0ZShcImRhdGEtcGpheC1jbGFzc2VzXCIpLCBcIlwiKVxuICAgICAgICAgIGVsLnJlbW92ZUF0dHJpYnV0ZShcImRhdGEtcGpheC1jbGFzc2VzXCIpXG4gICAgICAgIH1cbiAgICAgICAgZWwuY2xhc3NMaXN0LmFkZChcImpzLVBqYXgtcmVtb3ZlXCIpXG4gICAgICAgIGlmIChzd2l0Y2hPcHRpb25zLmNhbGxiYWNrcyAmJiBzd2l0Y2hPcHRpb25zLmNhbGxiYWNrcy5yZW1vdmVFbGVtZW50KSB7XG4gICAgICAgICAgc3dpdGNoT3B0aW9ucy5jYWxsYmFja3MucmVtb3ZlRWxlbWVudChlbClcbiAgICAgICAgfVxuICAgICAgICBpZiAoc3dpdGNoT3B0aW9ucy5jbGFzc05hbWVzKSB7XG4gICAgICAgICAgZWwuY2xhc3NOYW1lICs9IFwiIFwiICsgc3dpdGNoT3B0aW9ucy5jbGFzc05hbWVzLnJlbW92ZSArIFwiIFwiICsgKG9wdGlvbnMuYmFja3dhcmQgPyBzd2l0Y2hPcHRpb25zLmNsYXNzTmFtZXMuYmFja3dhcmQgOiBzd2l0Y2hPcHRpb25zLmNsYXNzTmFtZXMuZm9yd2FyZClcbiAgICAgICAgfVxuICAgICAgICBhbmltYXRlZEVsc051bWJlcisrXG4gICAgICAgIG9uKGVsLCBhbmltYXRpb25FdmVudE5hbWVzLCBzZXh5QW5pbWF0aW9uRW5kLCB0cnVlKVxuICAgICAgfVxuICAgIH0pXG5cbiAgICBmb3JFYWNoLmNhbGwobmV3RWwuY2hpbGROb2RlcywgZnVuY3Rpb24oZWwpIHtcbiAgICAgIGlmIChlbC5jbGFzc0xpc3QpIHtcbiAgICAgICAgdmFyIGFkZENsYXNzZXMgPSBcIlwiXG4gICAgICAgIGlmIChzd2l0Y2hPcHRpb25zLmNsYXNzTmFtZXMpIHtcbiAgICAgICAgICBhZGRDbGFzc2VzID0gXCIganMtUGpheC1hZGQgXCIgKyBzd2l0Y2hPcHRpb25zLmNsYXNzTmFtZXMuYWRkICsgXCIgXCIgKyAob3B0aW9ucy5iYWNrd2FyZCA/IHN3aXRjaE9wdGlvbnMuY2xhc3NOYW1lcy5mb3J3YXJkIDogc3dpdGNoT3B0aW9ucy5jbGFzc05hbWVzLmJhY2t3YXJkKVxuICAgICAgICB9XG4gICAgICAgIGlmIChzd2l0Y2hPcHRpb25zLmNhbGxiYWNrcyAmJiBzd2l0Y2hPcHRpb25zLmNhbGxiYWNrcy5hZGRFbGVtZW50KSB7XG4gICAgICAgICAgc3dpdGNoT3B0aW9ucy5jYWxsYmFja3MuYWRkRWxlbWVudChlbClcbiAgICAgICAgfVxuICAgICAgICBlbC5jbGFzc05hbWUgKz0gYWRkQ2xhc3Nlc1xuICAgICAgICBlbC5zZXRBdHRyaWJ1dGUoXCJkYXRhLXBqYXgtY2xhc3Nlc1wiLCBhZGRDbGFzc2VzKVxuICAgICAgICBlbHNUb0FkZC5wdXNoKGVsKVxuICAgICAgICBmcmFnVG9BcHBlbmQuYXBwZW5kQ2hpbGQoZWwpXG4gICAgICAgIGFuaW1hdGVkRWxzTnVtYmVyKytcbiAgICAgICAgb24oZWwsIGFuaW1hdGlvbkV2ZW50TmFtZXMsIHNleHlBbmltYXRpb25FbmQsIHRydWUpXG4gICAgICB9XG4gICAgfSlcblxuICAgIC8vIHBhc3MgYWxsIGNsYXNzTmFtZSBvZiB0aGUgcGFyZW50XG4gICAgb2xkRWwuY2xhc3NOYW1lID0gbmV3RWwuY2xhc3NOYW1lXG4gICAgb2xkRWwuYXBwZW5kQ2hpbGQoZnJhZ1RvQXBwZW5kKVxuXG4gICAgLy8gb2xkRWwuc3R5bGUuaGVpZ2h0ID0gcmVsZXZhbnRIZWlnaHQgKyBcInB4XCJcbiAgfVxufVxuIiwibW9kdWxlLmV4cG9ydHMgPSAoZnVuY3Rpb24oKSB7XG4gIHZhciBjb3VudGVyID0gMFxuICByZXR1cm4gZnVuY3Rpb24oKSB7XG4gICAgdmFyIGlkID0gKFwicGpheFwiICsgKG5ldyBEYXRlKCkuZ2V0VGltZSgpKSkgKyBcIl9cIiArIGNvdW50ZXJcbiAgICBjb3VudGVyKytcbiAgICByZXR1cm4gaWRcbiAgfVxufSkoKVxuIiwidmFyIGZvckVhY2hFbHMgPSByZXF1aXJlKFwiLi9mb3JlYWNoLWVsc1wiKVxuXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uKGVsZW1lbnRzLCBvbGRFbGVtZW50cykge1xuICAgdGhpcy5sb2coXCJzdHlsZWhlZXRzIG9sZCBlbGVtZW50c1wiLCBvbGRFbGVtZW50cyk7XG4gICB0aGlzLmxvZyhcInN0eWxlaGVldHMgbmV3IGVsZW1lbnRzXCIsIGVsZW1lbnRzKTtcbiAgdmFyIHRvQXJyYXkgPSBmdW5jdGlvbihlbnVtZXJhYmxlKXtcbiAgICAgIHZhciBhcnIgPSBbXTtcbiAgICAgIGZvcih2YXIgaSA9IGVudW1lcmFibGUubGVuZ3RoOyBpLS07IGFyci51bnNoaWZ0KGVudW1lcmFibGVbaV0pKTtcbiAgICAgIHJldHVybiBhcnI7XG4gIH07XG4gIGZvckVhY2hFbHMoZWxlbWVudHMsIGZ1bmN0aW9uKG5ld0VsLCBpKSB7XG4gICAgdmFyIG9sZEVsZW1lbnRzQXJyYXkgPSB0b0FycmF5KG9sZEVsZW1lbnRzKTtcbiAgICB2YXIgcmVzZW1ibGluZ09sZCA9IG9sZEVsZW1lbnRzQXJyYXkucmVkdWNlKGZ1bmN0aW9uKGFjYywgb2xkRWwpe1xuICAgICAgYWNjID0gKChvbGRFbC5ocmVmID09PSBuZXdFbC5ocmVmKSA/IG9sZEVsIDogYWNjKTtcbiAgICAgIHJldHVybiBhY2M7XG4gICAgfSwgbnVsbCk7XG5cbiAgICBpZihyZXNlbWJsaW5nT2xkICE9PSBudWxsKXtcbiAgICAgIGlmICh0aGlzLmxvZykge1xuICAgICAgICB0aGlzLmxvZyhcIm9sZCBzdHlsZXNoZWV0IGZvdW5kIG5vdCByZXNldHRpbmdcIik7XG4gICAgICB9XG4gICAgfSBlbHNlIHtcbiAgICAgIGlmICh0aGlzLmxvZykge1xuICAgICAgICB0aGlzLmxvZyhcIm5ldyBzdHlsZXNoZWV0ID0+IGFkZCB0byBoZWFkXCIpO1xuICAgICAgfVxuICAgICAgdmFyIGhlYWQgPSBkb2N1bWVudC5nZXRFbGVtZW50c0J5VGFnTmFtZSggJ2hlYWQnIClbMF0sXG4gICAgICAgbGluayA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoICdsaW5rJyApO1xuICAgICAgICBsaW5rLnNldEF0dHJpYnV0ZSggJ2hyZWYnLCBuZXdFbC5ocmVmICk7XG4gICAgICAgIGxpbmsuc2V0QXR0cmlidXRlKCAncmVsJywgJ3N0eWxlc2hlZXQnICk7XG4gICAgICAgIGxpbmsuc2V0QXR0cmlidXRlKCAndHlwZScsICd0ZXh0L2NzcycgKTtcbiAgICAgICAgaGVhZC5hcHBlbmRDaGlsZChsaW5rKTtcbiAgICB9XG4gIH0sIHRoaXMpO1xuXG59XG4iXX0=

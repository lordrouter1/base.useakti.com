(function () {
  var originalFetch = window.fetch;
  window.fetch = function (url, options) {
    options = options || {};
    if (options.signal) return originalFetch.call(window, url, options);
    var controller = new AbortController();
    var timeout = setTimeout(function () { controller.abort(); }, 30000);
    options.signal = controller.signal;
    return originalFetch.call(window, url, options).finally(function () { clearTimeout(timeout); });
  };
})();

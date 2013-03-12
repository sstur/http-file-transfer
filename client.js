/*global require, exports, module, process*/
(function() {
  "use strict";
  var URL = 'http://hft.local/hft.php';
  var USER = 'user';
  var PASS = 'password';

  var fs = require('fs');
  var join = require('path').join;
  var request = require('request');

  var r = req('post', '/image.png', 'sample/image.png', function(err, res) {
    console.log(err, res);
  });


  function req(method, path, file, callback) {
    method = method.toUpperCase();
    var r = request({
      uri: url(path),
      method: (method == 'GET') ? 'GET' : 'POST',
      headers: {
        'X-Method-Override': method
      },
      auth: {
        user: USER,
        pass: PASS,
        sendImmediately: true
      }
    }, callback);
    if (file) {
      var form = r.form();
      form.append('upload', fs.createReadStream(path(file)));
    }
    return r;
  }

  function path(pathname) {
    return join(__dirname, pathname);
  }

  function url(path) {
    if (path.charAt(0) != '/') {
      path = '/' + path;
    }
    return URL + '?path=' + path;
  }

})();
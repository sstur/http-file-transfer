<?php
$credentials = 'user|password';

DEFINE('BASEPATH', dirname(__FILE__));

if (!isset($_SERVER['PHP_AUTH_USER'])) {
  sendUnauthorized();
} else {
  $credentials = explode('|', $credentials);
  if ($_SERVER['PHP_AUTH_USER'] != $credentials[0] || $_SERVER['PHP_AUTH_PW'] != $credentials[1]) {
    sendUnauthorized();
  }
}

$headers = parseRequestHeaders();
if (!empty($headers['x-method-override'])) {
  $method = $headers['x-method-override'];
} else {
  $method = $_SERVER['REQUEST_METHOD'];
}
$method = strtolower($method);

if (!empty($_GET['path'])) {
  //rewrite uses querystring
  $path = $_GET['path'];
} else {
  $path = $_SERVER['REQUEST_URI'];
}
$path = normalize($path);

if ($method == 'get') {
  if (substr($path, -1) == '/') {
    $dirname = rtrim($path, '/');
    sendIndex($dirname);
  } else {
    sendFile($path);
  }
}

if ($method == 'post') {
  if (substr($path, -1) == '/') {
    $dirname = rtrim($path, '/');
    createDirectory($dirname);
    sendSuccess();
  } else {
    $dirname = dirname($path);
    createDirectory($dirname);
    if (!empty($_FILES['upload'])) {
      saveUpload($_FILES['upload'], $path);
    }
    sendSuccess();
  }
}

if ($method == 'delete') {
  if (substr($path, -1) == '/') {
    $dirname = rtrim($path, '/');
    removeDirectory($dirname);
    sendSuccess();
  } else {
    removeFile($path);
    sendSuccess();
  }
}



function createDirectory($path) {
  $fullpath = mapPath($path);
  if (!is_dir($fullpath)) {
    mkdir($fullpath, 0755, true);
  }
}

function removeDirectory($path) {
  $fullpath = mapPath($path);
  $items = scandir($fullpath);
  if ($items == false) {
    sendNotFound();
  }
  foreach ($items as $item) {
    $itempath = $path . '/' . $item;
    $itemfullpath = mapPath($itempath);
    if (is_dir($itemfullpath)) {
      removeDirectory($itempath);
    } else {
      removeFile($itempath);
    }
  }
  if (!rmdir($fullpath)) {
    sendError('Unable to remove directory: ' . $path, '500 Server Error');
  }
}

function removeFile($path) {
  if (!unlink(mapPath($path))) {
    sendError('Unable to delete file: ' . $path, '500 Server Error');
  }
}

function saveUpload($upload, $path) {
  if (!empty($upload['error'])) {
    sendError($upload['error']);
  }
  $fullpath = mapPath($path);
  if (!move_uploaded_file($upload['tmp_name'], $fullpath)) {
    sendError($upload['error']);
  }
}

function statFile($path) {
  $fullpath = mapPath($path);
  if (is_dir($fullpath)) {
    $stat = stat($fullpath);
    $stat['is_dir'] = true;
  } else
    if (is_file($fullpath)) {
      $stat = stat($fullpath);
      $stat['is_file'] = true;
    } else {
      $stat = array('enoent' => true);
    }
  return $stat;
}



function sendIndex($path) {
  $array = listFiles($path);
  if ($array == false) {
    sendNotFound();
  }
  echo implode("\n", $array);
}

function listFiles($path, &$array = array()) {
  $fullpath = mapPath($path);
  $items = scandir($fullpath);
  if ($items == false) {
    return false;
  }
  foreach ($items as $item) {
    if ($item == '.' || $item == '..') continue;
    $itempath = $path . '/' . $item;
    if (is_dir(mapPath($itempath))) {
      array_push($array, $itempath . '/');
      listFiles($itempath, $array);
    } else {
      array_push($array, $itempath);
    }
  }
  return $array;
}

function sendFile($path) {
  $fullpath = mapPath($path);
  if (file_exists($fullpath)) {
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($fullpath));
    flush();
    readfile($fullpath);
    exit;
  }
}



function sendSuccess() {
  echo '200 OK';
  exit;
}

function sendNotFound() {
  sendError('404 Not Found', '404 Not Found');
}

function sendError($error = 'Error processing request', $code = '400 Bad Request') {
  header($code);
  echo $error;
  exit;
}

function sendUnauthorized() {
  header('WWW-Authenticate: Basic');
  header('401 Unauthorized');
  echo '401 Unauthorized';
  exit;
}



function mapPath($path) {
  if (strlen($path) == 0) {
    return BASEPATH;
  }
  return BASEPATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
}

//normalize path by split into parts, decode, validate, join
function normalize($path) {
  if ($path == '/') return $path;
  $path = substr($path, 1);
  $is_dir = false;
  if (substr($path, -1) == '/') {
    $is_dir = true;
    $path = substr($path, 0, -1);
  }
  $parts = explode('/', $path);
  foreach ($parts as $i => $part) {
    $name = urldecode($part);
    if ($name == '' || $name == '.' || $name == '..') {
      sendError('Invalid path: ' . $path);
    }
    if (preg_match('/[^\x20-\x2E\x30-\x5B\x5D-\x7B\x7D-\x7E\w]/', $name)) {
      sendError('Invalid path: ' . $path);
    }
    $parts[$i] = $name;
  }
  $normalized = implode('/', $parts);
  if ($is_dir) $normalized .= '/';
  return $normalized;
}

function parseRequestHeaders() {
  $headers = array();
  foreach($_SERVER as $key => $value) {
    if (substr($key, 0, 5) <> 'HTTP_') {
      continue;
    }
    $header = str_replace('_', '-', strtolower(substr($key, 5)));
    $headers[$header] = $value;
  }
  return $headers;
}


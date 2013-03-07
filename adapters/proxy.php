<?php
if (!isset($_SERVER['PHP_AUTH_USER'])) {
  header('WWW-Authenticate: Basic');
  //todo: Check $_SERVER['SERVER_PROTOCOL'] ?
  header('401 Unauthorized');
  echo '401 Unauthorized';
  exit;
} else {
  //todo: check $_SERVER['PHP_AUTH_USER'] and $_SERVER['PHP_AUTH_PW']
}

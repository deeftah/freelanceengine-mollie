<?php
function begin($bearer, array $curl_post_data) {
  $url = "https://api.mollie.com/v2/payments";
  $curl = curl_init($url);
  $headers = [
    'Content-Type: application/json',
    sprintf('Authorization: Bearer %s', $bearer)
  ];
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data)); // sub-array support
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  $res = curl_exec($curl);
  $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  if ($http < 200 || $http > 299) {
    user_error(sprintf("HTTP(%s) => (%d) %s", $url, $http, $res));
  }
  curl_close($curl);
  $json = json_decode($res, true);
  return $json;
}

function status($bearer, $id) {
  $url = "https://api.mollie.com/v2/payments/$id";
  $curl = curl_init($url);
  $headers = [
    'Content-Type: application/json',
    sprintf('Authorization: Bearer %s', $bearer)
  ];
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  $res = curl_exec($curl);
  $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  if ($http < 200 || $http > 299) {
    user_error(sprintf("HTTP(%s) => (%d) %s", $url, $http, $res));
  }
  curl_close($curl);
  $json = json_decode($res, true);
  return $json;
}

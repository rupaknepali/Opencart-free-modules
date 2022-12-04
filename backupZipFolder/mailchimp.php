<?php

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://us2.api.mailchimp.com/3.0/lists/c1fc5f2e53/members/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\n    \"email_address\":\"r.u.k.np.l@gmail.com\", \n    \"status\":\"subscribed\",  \n    \"merge_fields\": {\n        \"FNAME\": \"Rupak Nepali\",\n        \"PHONE\": \"971233220\"\n    }\n}",
    CURLOPT_HTTPHEADER => array(
        "Authorization: apikey a6ee2a6d8634c6167faddb0d7b19e8d2-us2",
        "Content-Type: application/json",
        "Cookie: _mcid=1.b8b6993d1a5a5a9ae078d1bf3626d289.db30cd939dab9e12db277baeb8fd5c10e0f3b2d4368a90dbfaa8f51ff1b8bbf8; _AVESTA_ENVIRONMENT=prod; ak_bmsc=1F2048C78DDFB54F60E3E2336B5A7917B8321AC92A0800004533A25E9C2E7571~plSTLmke2GJby+5AMTEKdGESklGBOAYD054YHqbiGV+p57gSLkKTQxbnO/hfIppha47SDmeiRuSW9fEND4afeLrPG9ZaJgKRZwV+K8MSVKjFvXuV6JwUyKPe+b7T1aNY7wxcbtWcTF13E6xkRgmDdTrbg7vnj2i9Ddj974vqsTKqF3FC/KeCirc/o/sBt4PvQw3+/ceB28cr7M9EuQ7aG0S1KbsNwtHGhJdUfOYMvcyls=",
    ),
));

$response = curl_exec($curl);

curl_close($curl);
echo "<pre>";
$jd = json_decode($response);

if ($jd->status != "subscribed") {
    $msg = "Title:" . $jd->title . "<br>Status:" . $jd->status . "<br>" . $jd->detail;
    $msg .= "<br>----------";
}

echo $msg;

/***

"status": "subscribed"

{
"type": "http://developer.mailchimp.com/documentation/mailchimp/guides/error-glossary/",
"title": "Member Exists",
"status": 400,
"detail": "r.u.p.k.np.l@gmail.com is already a list member. Use PUT to insert or update list members.",
"instance": "fb722158-5bb2-4abd-b0ff-ee62aaca24c2"
}
 */
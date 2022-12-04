<?php
$toEmail = "member@testdomain.com";
$mailHeaders = "From: " . $_POST["userName"] . "<" . $_POST["userEmail"] . ">\r\n";
if (mail($toEmail, $_POST["subject"], $_POST["content"], $mailHeaders)) {
    print "<p class='success'>Contact Mail Sent.</p>";
} else {
    print "<p class='error'>Problem in Sending Mail.</p>";
}
?>
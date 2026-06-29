<?php
$urlEncoded = "uploads%2F11111111-1111-1111-1111-111111111111%2F96828148-418a-4443-b9ca-16a233085bfe%2F2953b96c8bc07b8c2e529c3fd23792fcdd5dcfbc.webp";
// $_GET receives the decoded version automatically. Let's see what happens if we use urldecode.
echo urldecode($urlEncoded);

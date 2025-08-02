<?php
$plainPassword = 'Youssef1';
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
echo $hashedPassword;
?>

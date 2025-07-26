<?php
$hash = '$2y$10$4MkPCKi50EacBq9XzPXBjeXG0qEfQ7eP9D2fXRXaQbUMmA49BQWvy';
$inputPassword = 'Admin20021!';

if (password_verify($inputPassword, $hash)) {
    echo "Password matches!";
} else {
    echo "Password does NOT match!";
}

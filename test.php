<?php

$password = '123456a@';
$hash_1 = password_hash($password, PASSWORD_DEFAULT);
echo $hash_1;
echo "<br>";
// See the password_hash() example to see where this came from.
$hash_2 = '$2y$10$7Du\/bbP17p\/2Un0mekGC1OVabgX5CqZNkj.e\/PyXsk0EXRAwxGsvC';

echo $hash_2;
echo "<br>";

if (password_verify($password, $hash_2)) {
    echo 'Password is valid!';
} else {
    echo 'Invalid password.';
}
?>
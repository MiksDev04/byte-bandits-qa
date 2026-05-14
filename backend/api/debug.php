<?php
echo json_encode([
    'sendgrid_key_set' => !empty(getenv('SENDGRID_API_KEY')),
    'from_address_set' => !empty(getenv('MAIL_FROM_ADDRESS')),
    'from_name'        => getenv('MAIL_FROM_NAME'),
]);
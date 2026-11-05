<?php
// Configure these values in the PHP/Apache environment. Never commit SMTP secrets.
return [
    'host' => getenv('SMTP_HOST') ?: '',
    'port' => (int)(getenv('SMTP_PORT') ?: 587),
    'encryption' => strtolower(getenv('SMTP_ENCRYPTION') ?: 'tls'), // tls, ssl, or none
    'username' => getenv('SMTP_USERNAME') ?: '',
    'password' => getenv('SMTP_PASSWORD') ?: '',
    'from_email' => getenv('MAIL_FROM') ?: '',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Electro Store',
];

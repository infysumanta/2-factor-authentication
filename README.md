Laravel Superadmin 2 Factor Extension


'extensions' => [
    'laravel-admin-google-authenticator' => [
        'enable' => true,
        'encrypt_key' => '',
        'encrypt_method' => 'AES-256-CBC',
        'encrypt_iv' => ''
    ],
],


php artisan vendor:publish --provider=Sumantablog\LaravelAdmin\Google\Authenticator\GoogleAuthenticatorServiceProvider

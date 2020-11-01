<?php

namespace Sumantablog\LaravelAdmin\Google\GoogleAuthenticator\Models;

use Sumantablog\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Sumantablog\LaravelAdmin\Google\GoogleAuthenticator\Core\PHPGangsta_GoogleAuthenticator;
use Sumantablog\Admin\Auth\Database\HasPermissions;

/**
 * Class Administrator.
 *
 * @property Role[] $roles
 */
class Administrator extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasPermissions;
    use DefaultDatetimeFormat;

    protected $fillable = ['username', 'password', 'name', 'avatar','google_auth'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = config('admin.database.connection') ?: config('database.default');

        $this->setConnection($connection);

        $this->setTable(config('admin.database.users_table'));

        parent::__construct($attributes);
    }

    /**
     * Get avatar attribute.
     *
     * @param string $avatar
     *
     * @return string
     */
    public function getAvatarAttribute($avatar)
    {
        if (url()->isValidUrl($avatar)) {
            return $avatar;
        }

        $disk = config('admin.upload.disk');

        if ($avatar && array_key_exists($disk, config('filesystems.disks'))) {
            return Storage::disk(config('admin.upload.disk'))->url($avatar);
        }

        $default = config('admin.default_avatar') ?: '/vendor/laravel-admin/AdminLTE/dist/img/user2-160x160.jpg';

        return admin_asset($default);
    }

    /**
     * A user has and belongs to many roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        $pivotTable = config('admin.database.role_users_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'role_id');
    }

    /**
     * A User has and belongs to many permissions.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        $pivotTable = config('admin.database.user_permissions_table');

        $relatedModel = config('admin.database.permissions_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'permission_id');
    }

    public function setGoogleAuthAttribute($val)
    {
        if (!config('admin.extensions.laravel-admin-google-authenticator.enable', true)) {
            // $this->attributes['google_auth'] = $val;
            return;
        }

        if (is_null($val)) {
            $google = new PHPGangsta_GoogleAuthenticator();
            $val= $google->createSecret();
        }

        $encrypt_key = config('admin.extensions.laravel-admin-google-authenticator.encrypt_key', '');
        $encrypt_method = config('admin.extensions.laravel-admin-google-authenticator.encrypt_method', 'AES-256-CBC');
        $encrypt_iv = md5(config('admin.extensions.laravel-admin-google-authenticator.encrypt_iv', ''), true);

        $this->attributes['google_auth'] = base64_encode(openssl_encrypt($val, $encrypt_method, $encrypt_key, OPENSSL_RAW_DATA, $encrypt_iv));
    }

    public function getGoogleAuthAttribute($val)
    {
        if (!config('admin.extensions.laravel-admin-google-authenticator.enable', true)) {
            return null;
        }

        if (is_null($val)) {
            // $google = new PHPGangsta_GoogleAuthenticator();
            // return $google->createSecret();
            return null;
        }

        $encrypt_key = config('admin.extensions.laravel-admin-google-authenticator.encrypt_key', '');
        $encrypt_method = config('admin.extensions.laravel-admin-google-authenticator.encrypt_method', 'AES-256-CBC');
        $encrypt_iv = md5(config('admin.extensions.laravel-admin-google-authenticator.encrypt_iv', ''), true);

        return openssl_decrypt(base64_decode($val), $encrypt_method, $encrypt_key, OPENSSL_RAW_DATA, $encrypt_iv);
    }

}
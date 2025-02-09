<?php
/**
 * src/Providers/EncryptServiceProvider.php.
 */

namespace ESolution\DBEncryption\Providers;

use ESolution\DBEncryption\Console\Commands\DecryptModel;
use ESolution\DBEncryption\Console\Commands\EncryptModel;
use ESolution\DBEncryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class DBEncryptionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * This method is called after all other service providers have
     * been registered, meaning you have access to all other services
     * that have been registered by the framework.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootValidators();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Config/config.php' => config_path('laravelDatabaseEncryption.php'),
            ], 'config');

            $this->commands([
                EncryptModel::class,
                DecryptModel::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('laravelDatabaseEncryption.php'),
        ], 'config');
    }

    private function bootValidators()
    {
        Validator::extend('unique_encrypted', function ($attribute, $value, $parameters, $validator) {
            // Initialize
            $salt = Encrypter::getKey();

            $withFilter = count($parameters) > 3 ? true : false;

            $ignore_id = isset($parameters[2]) ? $parameters[2] : '';

            // Check using normal checker
            $data = DB::table($parameters[0])->whereRaw("CONVERT(AES_DECRYPT(FROM_BASE64(`{$parameters[1]}`), '{$salt}') USING utf8mb4) = '{$value}' ");
            $data = $ignore_id != '' ? $data->where('id', '!=', $ignore_id) : $data;

            if ($withFilter) {
                $data->where($parameters[3], $parameters[4]);
            }

            if ($data->first()) {
                return false;
            }

            return true;
        });

        Validator::extend('exists_encrypted', function ($attribute, $value, $parameters, $validator) {
            // Initialize
            $salt = Encrypter::getKey();

            $withFilter = count($parameters) > 3 ? true : false;
            if (! $withFilter) {
                $ignore_id = isset($parameters[2]) ? $parameters[2] : '';
            } else {
                $ignore_id = isset($parameters[4]) ? $parameters[4] : '';
            }

            // Check using normal checker
            $data = DB::table($parameters[0])->whereRaw("CONVERT(AES_DECRYPT(FROM_BASE64(`{$parameters[1]}`), '{$salt}') USING utf8mb4) = '{$value}' ");
            $data = $ignore_id != '' ? $data->where('id', '!=', $ignore_id) : $data;

            if ($withFilter) {
                $data->where($parameters[2], $parameters[3]);
            }

            if ($data->first()) {
                return true;
            }

            return false;
        });
    }
}

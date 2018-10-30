<?php

namespace Fureev\Social\Providers;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Php\Support\Exceptions\InvalidConfigException;
use Php\Support\Exceptions\MissingConfigException;

/**
 * Class CustomProvider
 *
 * @package Laravel\Socialite\Two
 */
class CustomProvider extends AbstractProvider implements ProviderInterface
{
    use RedirectTrait;

    public $enabled = true;

    public $driverName;

    protected $config;

    protected $scopes;

    /**
     * @param array $config
     *
     * @throws \Exception
     */
    public function init(?array $config = null)
    {
        if ($config) {
            $this->setDriverConfig($config);
        }

        $this->scopes = $this->setScopes($this->getDriverConfig('scopes'));
    }


    /**
     * @param string $name
     *
     * @return $this
     */
    public function name(string $name)
    {
        $this->driverName = $name;

        return $this;
    }

    /**
     * @param null|string $key
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getDriverConfig(?string $key = null)
    {
        if (!$this->config) {
            if (!$this->driverName) {
                throw new \Exception('Missing Driver');
            }

            $conf = config('social.drivers');
            if (!$conf || !isset($conf[ $this->driverName ])) {
                throw new MissingConfigException($conf, 'social.drivers.' . $this->driverName);
            }

            if (!is_array($conf[ $this->driverName ])) {
                throw new InvalidConfigException($conf[ $this->driverName ]);
            }

            $this->config = $conf[ $this->driverName ];
        }

        return Arr::get($this->config, $key);
    }

    /**
     * @param array $config
     */
    protected function setDriverConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getDriverConfig('url_auth'), $state);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    protected function getTokenUrl()
    {
        return $this->getDriverConfig('url_token');
    }


    /**
     * @param array $user
     * {@inheritdoc}
     *
     * @return \Laravel\Socialite\Two\User
     * @throws \Exception
     */
    protected function mapUserToObject(array $user)
    {
        $fields = $this->getDriverConfig('mapFields');
        array_walk($fields, function (&$val, $k, $user) {
            $val = Arr::get($user, $val);
        }, $user);

        return (new User)->setRaw($user)->map($fields);
    }

    /**
     * @param string $token
     *
     * @return array|void
     * @throws \Exception
     */
    protected function getUserByToken($token)
    {
        throw new \Exception();
    }

}

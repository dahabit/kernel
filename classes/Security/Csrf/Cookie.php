<?php

namespace Fuel\Kernel\Security\Csrf;
use Fuel\Kernel\Application;

class Cookie extends Base
{
	/**
	 * @var  string  token key used in cookie
	 */
	protected $token_key = 'fuel_csrf_token';

	/**
	 * @var  null|string  token for the next Request
	 */
	protected $new_token;

	public function _set_app(Application\Base $app)
	{
		parent::_set_app($app);

		$this->token_key = $this->app->config->get('security.csrf_token_key', 'fuel_csrf_token');
	}

	public function update_token($force_reset = false)
	{
		$old_token = $this->app->active_request()->input->cookie($this->token_key);

		// re-use old token when found (= not expired) and expiration is used (otherwise always reset)
		if ( ! $force_reset and $old_token and $this->app->config->get('security.csrf_expiration', 0) > 0)
		{
			$this->new_token = $old_token;
		}
		// set new token for next session when necessary
		else
		{
			$this->new_token = md5(uniqid().time());

			$expiration = $this->app->config->get('security.csrf_expiration', 0);
			// @todo implement cookie class
			// \Cookie::set(static::$csrf_token_key, static::$csrf_token, $expiration);
		}
	}

	public function get_token()
	{
		if (is_null($this->new_token))
		{
			$this->update_token();
		}

		return $this->new_token;
	}

	public function check_token($value = null)
	{
		$value = $value ?: $this->app->active_request()->input->param($this->token_key, null);
		$old_token = $this->app->active_request()->input->cookie($this->token_key);

		// always reset token once it's been checked and still the same
		if ($this->get_token() == $old_token and ! empty($value))
		{
			$this->update_token(true);
		}

		return $value === $old_token;
	}
}

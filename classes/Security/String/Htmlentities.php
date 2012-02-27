<?php

namespace Fuel\Kernel\Security\String;

class Htmlentities extends Base
{
	public function clean($input)
	{
		return htmlentities($input, ENT_QUOTES, _env('encoding'), false);
	}
}

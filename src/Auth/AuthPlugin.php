<?php

declare(strict_types=1);

namespace ListDAV\Auth;

use Sabre\DAV\Auth\Backend\AbstractBasic;

class AuthPlugin extends AbstractBasic
{
    public function __construct(
        private readonly AuthInterface $auth,
    ) {}

    protected function validateUserPass($username, $password): bool
    {
        return $this->auth->authenticate($username, $password) !== null;
    }
}

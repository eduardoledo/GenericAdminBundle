<?php

namespace Lomaswifi\AdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LomaswifiAdminBundle extends Bundle
{

    public function getParent()
    {
        return 'FOSUserBundle';
    }

}

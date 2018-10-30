<?php

namespace Fureev\Social\Providers;

/**
 * Trait RedirectTrait
 *
 * @package Fureev\Social\Providers
 */
trait RedirectTrait
{
    /**
     * @return string
     */
    public function getRedirectPath()
    {
        return $this->redirectUrl;
    }

}

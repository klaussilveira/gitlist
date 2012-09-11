<?php

/*
 * This file is part of the Gitter library.
 *
 * (c) Klaus Silveira <klaussilveira@php.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gitter\Model;

abstract class AbstractModel
{
    protected $repository;

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }
}

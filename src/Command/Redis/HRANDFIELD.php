<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\With\WithValues;

/**
 * @link https://redis.io/commands/hrandfield/
 *
 * When called with just the key argument, return a random field from the hash value stored at key.
 *
 * If the provided count argument is positive, return an array of distinct fields.
 * The array's length is either count or the hash's number of fields (HLEN), whichever is lower.
 */
class HRANDFIELD extends RedisCommand
{
    use WithValues;

    public function getId()
    {
        return 'HRANDFIELD';
    }
}

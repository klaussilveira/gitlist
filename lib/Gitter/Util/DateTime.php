<?php
// lib/Gitter/Util/DateTime.php

/*
 * This file is part of the Gitter library.
 *
 * (c) Klaus Silveira <klaussilveira@php.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gitter\Util;

/**
 * Fixes the issue that the $timezone parameter and the current timezone are ignored when
 * the $time parameter either is a UNIX timestamp (e.g. @946684800) or specifies a timezone
 * (e.g. 2010-01-28T15:00:00+02:00).
 *
 * @link https://github.com/klaussilveira/gitlist/issues/140
 */
class DateTime extends \DateTime
{
    /**
     * @const The regular expression for an UNIX timestamp
     */
    const UNIX_TIMESTAMP_PATTERN = '/^@\d+$/';

    /**
     * @param string       $time     A date/time string.
     * @param DateTimeZone $timezone A DateTimeZone object representing the desired time zone.
     *
     * @return DateTime A new DateTime instance.
     *
     * @link http://php.net/manual/en/datetime.construct.php
     */
    public function __construct($time = 'now', DateTimeZone $timezone = null)
    {
        if ($timezone) {
            parent::__construct($time, $timezone);
        }
        else {
            parent::__construct($time);
        }

        if ($this->isUnixTimestamp($time)) {
            if (!$timezone)
                $timezone = new \DateTimeZone(date_default_timezone_get());

            $this->setTimezone($timezone);
        }
    }

    /**
     * Checks if an UNIX timestamp is passed.
     *
     * @param string $time A date/time string.
     *
     * @return bool true if the $time parameter is an UNIX timestamp, unless false
     */
    protected function isUnixTimestamp($time)
    {
        if (preg_match(self::UNIX_TIMESTAMP_PATTERN, $time))
            return true;

        return false;
    }
}

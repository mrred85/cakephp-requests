<?php

/**
 * Requests Utility Interface
 *
 * @link https://github.com/mrred85/cakephp-requests
 * @copyright 2016 - present Victor Rosu. All rights reserved.
 * @license Licensed under the MIT License.
 */

namespace App\Utility;

interface RequestsInterface
{
    /**
     * Get request output
     *
     * @return string|bool
     */
    public function getOutput();

    /**
     * Get request HTTP response code
     *
     * @return int
     */
    public function getHttpResponseCode(): int;

    /**
     * Total transaction time in seconds for last transfer
     *
     * @return float
     */
    public function getTotalTime(): float;

    /**
     * Get request error number
     *
     * @return int
     */
    public function getErrorNumber(): int;

    /**
     * Get request error message
     *
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * Get textual representation of error code
     *
     * @return string
     */
    public function getErrorCodeMessage(): string;
}

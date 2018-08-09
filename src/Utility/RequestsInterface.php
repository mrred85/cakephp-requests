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
     * @return string|null
     */
    public function getOutput();

    /**
     * Get request HTTP response code
     *
     * @return int|null
     */
    public function getHttpResponseCode();

    /**
     * Get request error number
     *
     * @return int|null
     */
    public function getErrorNumber();

    /**
     * Get request error message
     *
     * @return string|null
     */
    public function getErrorMessage();
}

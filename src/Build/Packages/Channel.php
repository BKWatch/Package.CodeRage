<?php

/**
 * Defines the class CodeRage\Build\Packages\Channel.
 *
 * File:        CodeRage/Build/Packages/Channel.php
 * Date:        Mon Jan 28 15:28:58 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages;

/**
 * @ignore
 */

/**
 * Represents information about a channel.
 */
class Channel extends \CodeRage\Util\BasicProperties {

    /**
     * Returns the URL of this channel.
     *
     * @var string
     */
    private $url;

    /**
     * Returns the username of the account, if any, to be used to access this
     * channel.
     *
     * @var string
     */
    private $username;

    /**
     * Returns the password of the account, if any, to be used to access this
     * channel.
     *
     * @var string
     */
    private $password;

    /**
     * Constructs a CodeRage\Build\Packages\Channel.
     *
     * @param string $url The URL of the channel under construction.
     * @param string $username The username of the account, if any, to
     * be used to access the channel under construction.
     * @param string $password The password of the account, if any, to
     * be used to access the channel under construction.
     */
    function __construct($url, $username = null, $password = null)
    {
        parent::__construct();
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Returns the URL of this channel.
     */
    function url() { return $this->url; }

    /**
     * Returns the username of the account, if any, to be used to access this
     * channel.
     *
     * @return string
     */
    function username() { return $this->username; }

    /**
     * Returns the password of the account, if any, to be used to access this
     * channel.
     *
     * @return string
     */
    function password() { return $this->password; }
}

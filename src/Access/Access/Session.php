<?php

/**
 * Defines the class CodeRage\Access\Session, representing an access control
 * session
 *
 * File:        CodeRage/Access/Session.php
 * Date:        Wed Mar  8 19:10:57 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use CodeRage\Db;
use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Random;
use CodeRage\Util\Time;


class Session {

    /**
     * @var int
     */
    const SESSIONID_LENGTH = 64;

    /**
     * @var int
     */
    const DEFAULT_LIFETIME = 3600;

    /**
     * Constructs an instance of CodeRage\Access\Session
     *
     * @param array $options The options array; supports the following options:
     *     id - The database ID
     *     sessionid - The alphanumeric session identifier
     *     user - The user, as an instance of CodeRage\Access\User
     *     group - The group, if any, defining the access rights of the user, as
     *       an instance of CodeRage\Access\Group (optional)
     *     lifetime - The initial session lifetime in seconds, which is also the
     *       ammount by which the expiration date is incremented when the
     *       session is updated
     *     expires - The expiration date, as a UNIX timestamp
     *     data - Data associated with the session, as a native data structure
     *       (optional)
     *     ipAddress - The user's IP address(optional)
     */
    private function __construct($options)
    {
        $this->id = $options['id'];
        $this->sessionid = $options['sessionid'];
        $this->user = $options['user'];
        $this->group = $options['group'];
        $this->lifetime = $options['lifetime'];
        $this->expires = $options['expires'];
        $this->data = $options['data'];
        $this->ipAddress = $options['ipAddress'];
    }

    /**
     * Returns the database ID
     *
     * @return int
     */
    public function id() { return $this->id; }

    /**
     * Returns the alphanumeric session identifier
     *
     * @return string
     */
    public function sessionid() { return $this->sessionid; }

    /**
     * Returns the user
     *
     * @return CodeRage\Access\User
     */
    public function user() { return $this->user; }

    /**
     * Returns the group, if any, defining the access rights of the user
     *
     * @return CodeRage\Access\Group
     */
    public function group() { return $this->group; }

    /**
     * Returns the initial session lifetime in seconds, which is also the
     * ammount by which the expiration date is incremented when the session is
     * updated
     *
     * @return int
     */
    public function lifetime() { return $this->lifetime; }

    /**
     * Returns expiration date, as a UNIX timestamp
     *
     * @return int
     */
    public function expires() { return $this->expires; }

    /**
     * Returns the data associated with the session, if any, as a native data
     * structure
     *
     * @return stdClass
     */
    public function data() { return $this->data; }

    /**
     * Sets the data associated with the session
     *
     * @param stdClass $data A native data structure
     */
    public function setData($data)
    {
        Args::check($data, 'stdClass', 'data');
        $this->data = $data;
    }

    /**
     * Returns the user's IP address, if known
     *
     * @return string
     */
    public function ipAddress() { return $this->ipAddress; }

    /**
     * Sets the user's IP address
     *
     * @param string $ipAddress The IP address
     */
    public function setIpAddress($ipAddress)
    {
        Args::check($ipAddress, 'string', 'IP address');
        $this->ipAddress = $ipAddress;
    }

    /**
     * Updates the expiration date of this session
     */
    public function touch()
    {
        $sql =
            'UPDATE AccessSession
             SET expires = %i + lifetime
             WHERE RecordID = %i';
        self::db()->query($sql, $this->id, Time::get());
    }

    /**
     * Returns the current session, if any
     *
     * @return CodeRage\Access\Session
     */
    public static function current() { return self::$current; }

    /**
     * Sets the current session
     *
     * @param CodeRage\Access\Session $current The session
     */
    public static function setCurrent(Session $current)
    {
        Args::check($current, 'CodeRage\\Access\\Session', 'data');
        self::$current = $current;
    }

    /**
     * Create a new instance of CodeRage\Access\Session and saves it to the
     * database
     *
     * @param array $options The options array; supports the following options:
     *     userid - The user ID
     *     groupid - The ID of the group, if any, defining the access rights of
     *       the user (optional)
     *     lifetime - The initial session lifetime in seconds, which is also the
     *       ammount by which the expiration date is incremented when the
     *       session is updated
     *     data - Data associated with the session, as a native data structure
     *       (optional)
     *     ipAddress - The user's IP address (optional)
     * @return CodeRage\Access\Session
     */
    public static function create($options)
    {
        Args::checkIntKey($options, 'userid', [
            'label' => 'user ID',
            'required' => true
        ]);
        $user = User::load(['id' => $options['userid']]);
        Args::checkIntKey($options, 'groupid', [
            'label' => 'group ID',
            'default' => null
        ]);
        $group = isset($options['groupid']) ?
            Group::load(['id' => $options['groupid']]) :
            null;
        Args::checkIntKey($options, 'lifetime', [
            'default' => self::DEFAULT_LIFETIME
        ]);
        $expires = Time::get() + $options['lifetime'];
        Args::checkKey($options, 'data', 'stdClass', [
            'default' => new \stdClass
        ]);
        $json = json_encode($options['data']);
        if ($json === null)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid session data: JSON encoding failed'
                ]);
        Args::checkKey($options, 'ipAddress', 'string', [
            'label' => 'IP address',
            'default' => null
        ]);
        $sessionid = Random::string(self::SESSIONID_LENGTH);
        $id =
            self::db()->insert(
                'AccessSession',
                [
                    'sessionid' => $sessionid,
                    'userid' => $options['userid'],
                    'groupid' => $options['groupid'],
                    'lifetime' => $options['lifetime'],
                    'expires' => $expires,
                    'data' => $json,
                    'ipAddress' => $options['ipAddress']
                ]
            );
        return new
            Session([
                'id' => $id,
                'sessionid' => $sessionid,
                'user' => $user,
                'group' => $group,
                'lifetime' => $options['lifetime'],
                'expires' => $expires,
                'data' => $options['data'],
                'ipAddress' => $options['ipAddress']
            ]);
    }

    /**
     * Loads an existing session from the database, or creates a new session and
     * saves it to the database
     *
     * @param array $options The options array; supports the following options:
     *     username - The username (optional)
     *     password - The password (optional)
     *     authtoken - The authorization token (optional)
     *     sessionid - The session ID (optional)
     *     lifetime - The initial session lifetime in seconds, which is also the
     *       ammount by which the expiration date is incremented when the
     *       session is updated
     *     ipAddress - The user's IP address (optional) Exactly one of
     *       "username", "authtoken", and "sessionid" must be supplied;
     *       "password" must be suppied if and only if "username" is supplied;
     *       "sessionid" may not be used in combination with other options.
     * @return CodeRage\Access\Session
     * @throws CodeRage\Error if a session ID is supplied and no such session
     *   exists, or if the session is expired
     */
    public static function authenticate($options)
    {
        $count = 0;
        if (isset($options['username']))
            ++$count;
        if (isset($options['authtoken']))
            ++$count;
        if (isset($options['sessionid']))
            ++$count;
        if ($count > 1)
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "At most one of the options 'username', 'authtoken', " .
                        "and 'sessionid' may be specified"
                ]);
        if ($count == 0)
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' =>
                        "Missing 'username', 'authtoken', or 'sessionid'"
                ]);
        if ( isset($options['username']) && !isset($options['password']) ||
             isset($options['password']) && !isset($options['username']) )
        {
            $missing = isset($options['username']) ?
                'password' :
                'username';
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => "Missing '$missing'"
                ]);
        }
        if ( isset($options['sessionid']) &&
             (isset($options['lifetime']) || isset($options['ipAddress'])) )
        {
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The option 'sessionid' may not be combined with " .
                        "other options"
                ]);
        }
        Args::checkKey($options, 'username', 'string');
        Args::checkKey($options, 'password', 'string');
        Args::checkKey($options, 'authtoken', 'string');
        Args::checkKey($options, 'sessionid', 'string', [
            'label' => 'session ID'
        ]);
        if (isset($options['username'])) {
            $username = $options['username'];
            $password = $options['password'];
            $sql =
                'SELECT u.RecordID {i}, u.password {s}
                 FROM AccessUser u
                 JOIN AccessResource r
                   ON r.RecordID = u.resource
                 WHERE u.username = %s AND
                       r.disabled IS NULL AND
                       r.retired IS NULL';
            $row = self::db()->fetchFirstRow($sql, $username);
            if ($row === null)
                throw new
                    Error([
                        'status' => 'UNAUTHORIZED',
                        'message' => 'Invalid username or password'
                    ]);
            list($userid, $hash) = $row;
            if (!Hash::verify($options['password'], $hash))
                throw new
                    Error([
                        'status' => 'UNAUTHORIZED',
                        'message' => 'Invalid username or password'
                    ]);
            if (Hash::needsRehash($hash)) {
                $hash = Hash::generate($options['password']);
                self::db()->update(
                    'AccessUser',
                    ['password' => $hash],
                    ['username' => $username]
                );
            }
            return self::create([
                        'userid' => $userid,
                        'lifetime' => isset($options['lifetime']) ?
                            $options['lifetime'] :
                            null,
                        'ipAddress' => isset($options['ipAddress']) ?
                            $options['ipAddress'] :
                            null
                   ]);
        } elseif (isset($options['authtoken'])) {
            $sql =
                'SELECT t.RecordID {i}, t.userid {i}, t.groupid {i},
                        t.expires {i}
                 FROM AccessAuthToken t
                 JOIN AccessResource r
                   ON r.RecordID = t.resource
                 WHERE t.value = %s AND
                       r.disabled IS NULL AND
                       r.retired IS NULL';
            $row = self::db()->fetchFirstRow($sql, $options['authtoken']);
            if ($row === null)
                throw new
                    Error([
                        'status' => 'UNAUTHORIZED',
                        'message' => 'Invalid authorization token'
                    ]);
            list($id, $userid, $groupid, $expires) = $row;
            if ($expires < Time::get()) {
                self::db()->delete('AccessAuthToken', $id);
                throw new
                    Error([
                        'status' => 'UNAUTHORIZED',
                        'message' => 'Expired authorization token'
                    ]);
            }
            return self::create([
                        'userid' => $userid,
                        'groupid' => $groupid,
                        'lifetime' => isset($options['lifetime']) ?
                            $options['lifetime'] :
                            null,
                        'ipAddress' => isset($options['ipAddress']) ?
                            $options['ipAddress'] :
                            null
                   ]);
        } else {
            return self::load(['sessionid' => $options['sessionid']]);
        }
    }

    /**
     * Loads an existing session from the database
     *
     * @param array $options The options array; accepts the following options:
     *     id - The database ID
     *     sessionid - The alphanumeric session identifier
     *     touch - True to tocuh the session after loading
     *   Exactly one of "id" and "sessionid" must be supplied.
     * @return CodeRage\Access\Session
     * @throws CodeRage\Error if no such session exists, or if the session is
     *   expired
     */
    public static function load($options)
    {
        $opt = Args::uniqueKey($options, ['id', 'sessionid']);
        Args::checkKey($options, 'touch', 'boolean', [
            'default' => true
        ]);
        $sql =
            'SELECT *
             FROM AccessSession
             WHERE expires > %i AND ' .
            ($opt == 'id' ? 'RecordID = %i' : 'sessionid = %s');
        $now = Time::get();
        $value = $options[$opt];
        $row = self::db()->fetchFirstArray($sql, $now, $value);
        if ($row === null)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => 'Your session has expired',
                    'details' =>
                        "No session exists with " .
                        ($opt == 'id' ? "ID $value" : "sessionid '$value'")
                ]);
        $user = User::load(['id' => (int) $row['userid']]);
        $group = isset($row['groupid']) ?
            Group::load(['id' => $row['groupid']]) :
            null;
        $data = isset($row['data']) ?
            json_decode($row['data']) :
            null;
        $expires = $options['touch'] ?
            $now + $row['lifetime'] :
            $row['expires'];
        $session =
            new Session([
                'id' => (int) $row['RecordID'],
                'sessionid' => $row['sessionid'],
                'user' => $user,
                'group' => $group,
                'lifetime' => $row['lifetime'],
                'expires' => $expires,
                'data' => $data,
                'ipAddress' => $row['ipAddress']
            ]);
        if ($options['touch']) {
            self::db()->update(
                'AccessSession',
                ['expires' => $expires],
                $session->id
            );
        }
        return $session;
    }

    /**
     * Updates the record in the table AccessSession corresponding to this
     * session
     */
    public function save()
    {
        $json = json_encode($this->data);
        if ($json === null)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' => 'Invalid session data: JSON encoding failed'
                ]);
        self::db()->update(
            'AccessSession',
            [
                'data' => $json,
                'ipAddress' => $this->ipAddress
            ],
            $this->id
        );
    }

    /**
     * Deletes this session
     */
    public function delete()
    {
        self::db()->delete('AccessSession', $this->id);
    }

    public function nativeDataEncode(\CodeRage\Util\NativeDataEncoder $encoder)
    {
        $encoding = (object)
            [
                'sessionid' => $this->sessionid,
                'user' => $this->user,
                'group' => $this->group,
                'lifetime' => $this->lifetime,
                'expires' => $this->expires,
                'data' => $this->data
            ];
        if ($this->group === null)
            unset($encoding->group);
        if (count(get_object_vars($this->data)) == 0)
            unset($encoding->data);
        return $encoding;
    }

    /**
     * Returns an instance of CodeRage::Db that doesn't use a cached connection
     *
     * @return CodeRage\Db
     */
    private static function db()
    {
        return Db::nonNestableInstance();
    }

    /**
     * @var CodeRage\Access\Session
     */
    private static $current;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $sessionid;

    /**
     * @var CodeRage\Access\User
     */
    private $user;

    /**
     * @var CodeRage\Access\Group
     */
    private $group;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var int
     */
    private $expires;

    /**
     * @var stdClass
     */
    private $data;

    /**
     * @var string
     */
    private $ipAddress;
}

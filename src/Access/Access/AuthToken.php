<?php

/**
 * Defines the class CodeRage\Access\AuthToken
 *
 * File:        CodeRage/Access/AuthToken.php
 * Date:        Tue Jul 26 18:30:16 UTC 2016
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2016 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use DateTime;
use CodeRage\Db;
use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Random;


/**
 * Managages alphanumeric identifiers that can be used for authentication
 */
final class AuthToken implements Managed {

    /**
     * The token length
     *
     * @var int
     */
    const TOKEN_LENGTH = 255;

    /**
     * The maximum number of tokens returned by listTokens()
     *
     * @var int
     */
    const MAX_TOKEN_LIFETIME = 365 * 24 * 3600;

    /**
     * The maximum number of tokens returned by listTokens()
     *
     * @var int
     */
    const MAX_TOKEN_COUNT = 100;

    /**
     * Constructs an instance of CodeRage\Access\AuthToken
     *
     * @param array $options The options array; supports the following options:
     *     id - The database ID
     *     value - The alphanumeric identifier
     *     user - The user authenticated by the token, as an instance of
     *       CodeRage\Access\User
     *     group - The group, if any, defining the access rights of
     *       the authenticated user, as an instance of CodeRage\Access\Group
     *       (optional)
     *     label - A descriptive label (optional)
     *     created - The creation date, as a UNIX timestamp
     *     expires - The expiration date, as a UNIX timestamp
     *     resource - The resource associated with the CodeRage\Access\AuthToken
     *       under construction, as an instance of CodeRage\Access\Resource_
     */
    private function __construct (array $options)
    {
        $this->id = $options['id'];
        $this->value = $options['value'];
        $this->user = $options['user'];
        $this->group = isset($options['group']) ?
            $options['group'] :
            null;
        $this->label = isset($options['label']) ?
            $options['label'] :
            null;
        $this->created = $options['created'];
        $this->expires = $options['expires'];
        $this->resource = $options['resource'];
    }

    /**
     * Returns the database ID
     *
     * @return int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Retruns the alphanumeric identifier
     *
     * @return string
     */
    public function value() { return $this->value; }

    /**
     * Retruns the the user that this token authenticates
     *
     * @return CodeRage\Access\User
     */
    public function user() { return $this->user; }

    /**
     * Returns the group, if any, defining the access rights of the
     * authenticated user
     *
     * @return CodeRage\Access\Group
     */
    public function group() { return $this->group; }

    /**
     * Retruns the descriptive label, if any
     *
     * @return string
     */
    public function label() { return $this->label; }

    /**
     * Retruns the creation date, as a UNIX timestamp
     *
     * @return int
     */
    public function created() { return $this->created; }

    /**s
     * Retruns the expiration date, as a UNIX timestamp
     *
     * @return int
     */
    public function expires() { return $this->expires; }

    /**
     * Returns the resource associated with this CodeRage\Access\AuthToken
     *
     * @return CodeRage\Access\Resource_
     */
    public function resource() { return $this->resource; }

    /**
     * Returns the result of encoding this object as a native data structure
     *
     * @param CodeRage\Util\NativeDataEncoder $encoder The native data encoder
     */
    public function nativeDataEncode(\CodeRage\Util\NativeDataEncoder $encoder)
    {
        $result =
            [
                'value' => $this->value,
                'user' => ResourceId::encode($this->user->id(), 'user')
            ];
        if ($this->group !== null)
            $result['group'] = ResourceId::encode($this->group->id(), 'group');
        if ($this->label !== null)
            $result['groupid'] = $this->label;
        $utc = new \DateTimeZone('UTC');
        $result['created'] =
            (new DateTime(null, $utc))
                ->setTimestamp($this->created)
                ->format(DATE_W3C);
        $result['expires'] =
            (new DateTime(null, $utc))
                ->setTimestamp($this->expires)
                ->format(DATE_W3C);
        return (object) $result;
    }

    /**
     * Creates an authorization token
     *
     * @params array $options The options array; supports the following options:
     *     userid - The user ID that the token authenticates, as an integer
     *     owner - The ID of the user creating the token, as an integer;
     *       defaults to the value of 'userid'
     *     groupid - The ID of the group, if any, defining the access rights of
     *       the authenticated user, as an integer (optional)
     *     label - A descriptive label  (optional)
     *     lifetime - The lifetime, in seconds
     *  @return CodeRage\Access\AuthToken
     */
    public static function create(array $options)
    {
        Args::checkKey($options, 'userid', 'int', [
            'label' => 'user ID',
            'required' => true
        ]);
        Args::checkKey($options, 'owner', 'int', [
            'label' => 'owner',
            'default' => $options['userid']
        ]);
        Args::checkKey($options, 'groupid', 'int', [
            'label' => 'group ID',
            'default' => null
        ]);
        Args::checkKey($options, 'label', 'string');
        Args::checkKey($options, 'lifetime', 'int', [
            'required' => true
        ]);
        $lifetime = $options['lifetime'];
        if ($lifetime <= 0 || $lifetime > self::MAX_TOKEN_LIFETIME)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid lifetime: $lifetime"
                ]);
        $db = new Db;
        $db->beginTransaction();
        $token = null;
        try {
            $value = Random::string(self::TOKEN_LENGTH);
            $user = User::load(['id' => $options['userid']]);
            $group = isset($options['groupid']) ?
                Group::load(['id' => $options['groupid']]) :
                null;
            $label = isset($options['label']) ?
                $options['label'] :
                null;
            $created = \CodeRage\Util\Time::get();
            $expires = $created + $options['lifetime'];
            $resource = Resource_::create('auth', $options['owner']);
            $id =
                $db->insert(
                    'AccessAuthToken',
                    [
                        'CreationDate' => $created,
                        'value' => $value,
                        'userid' => $options['userid'],
                        'groupid' => isset($options['groupid']) ?
                             $options['groupid'] :
                             null,
                        'label' => $label,
                        'expires' => $expires,
                        'resource' => $resource->id()
                    ]
                );
            $token =
                new AuthToken([
                        'id' => $id,
                        'value' => $value,
                        'user' => $user,
                        'group' => $group,
                        'label' => $label,
                        'created' => $created,
                        'expires' => $expires,
                        'resource' => $resource
                    ]);
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
        $db->commit();
        return $token;
    }

    /**
     * Returns the authorization token with the specified ID or value
     *
     * @params array $options The options array; must contain exactly one of the
     *   following options
     *     id - The database ID
     *     value - The alphanumeric identifier
     * @return CodeRage\Access\AuthToken
     * @throws CodeRage\Error if no such token exists
     */
    public static function load(array $options)
    {
        $opt = Args::uniqueKey($options, ['id', 'value']);
        $val = $options[$opt];
        $col = $ph = null;
        if ($opt == 'id') {
            $col = 'RecordID';
            $ph = '%i';
        } else {
            $col = 'value';
            $ph = '%s';
        }
        $db = new Db;
        $sql =
            "SELECT *
             FROM AccessAuthToken
             WHERE [$col] = $ph";
        $row = $db->fetchFirstArray($sql, $val);
        if ($row == null) {
            $ident = $opt == 'id' ?
                "ID $val" :
                "value '$val'";
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'message' => 'No such authorization token',
                    'details' => "No authorization token exists with $ident"
                ]);
        }
        return new
            AuthToken([
                'id' => (int) $row['RecordID'],
                'value' => $row['value'],
                'user' => User::load(['id' => (int) $row['userid']]),
                'group' => $row['groupid'] !== null ?
                    Group::load(['id' => (int) $row['groupid']]) :
                    null,
                'label' => $row['label'],
                'created' => (int) $row['CreationDate'],
                'expires' => (int) $row['expires'],
                'resource' => Resource_::load(['id' => (int) $row['resource']])
            ]);
    }

    /**
     * Deletes this authorization token
     */
    public function delete()
    {
        (new Db)->runInTransaction(function($db) {
            $db->delete('AccessAuthToken', $this->id);
            $this->resource->delete();
        });
    }

    /**
     * The database ID
     *
     * @var string
     */
    private $id;

    /**
     * An alphanumeric identifier
     *
     * @var string
     */
    private $value;

    /**
     * The user that this token authenticates
     *
     * @var CodeRage\Access\User
     */
    private $user;

    /**
     * The group, if any, defining the access rights of the authenticated user,
     * as an integer (optional) (not currently  supported)
     *
     * @var CodeRage\Access\Group
     */
    private $group;

    /**
     * The descriptive label, if any
     *
     * @var string
     */
    private $label;

    /**
     * The creation date, as a UNIX timestamp
     *
     * @var int
     */
    private $created;

    /**
     * The expiration date, as a UNIX timestamp
     *
     * @var int
     */
    private $expires;

    /**
     * The resource associated with this CodeRage\Access\AuthToken
     *
     * @var CodeRage\Access\Resource_
     */
    private $resource;
}

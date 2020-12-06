<?php

/**
 * Defines the class CodeRage\Access\User
 *
 * File:        CodeRage/Access/User.php
 * Date:        Sun Jun 10 12:45:50 EDT 2012
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use Throwable;
use CodeRage\Db;
use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Random;


/**
 * Represents a user in the access control system.
 */
final class User implements Managed {

    /**
     * The ID of the anonymous user
     *
     * @var int
     */
    const ANONYMOUS = 0;

    /**
     * The ID of the root user
     *
     * @var int
     */
    const ROOT = 1;

    /**
     * @var string
     */
    const MATCH_NAME = '/^([-._a-zA-Z0-9]+:)?[-._a-zA-Z0-9]+$/x';

    /**
     * @var int
     */
    const NAME_MAX_LENGTH = 200;

    /**
     * @var int
     */
    const RANDOM_PASSWORD_LENGTH = 30;

    /**
     * Constructs an instance of CodeRage\Access\User.
     *
     * @param array $options The options array; supports the following options:
     *     id - The database ID
     *     username The username
     *     publicGroupId - The ID of the user's public group, i.e., the group
     *       into which shared resources which the user has permission to view
     *       are placed
     *     privateGroupId - The ID of the user's private group, i.e., the group
     *       into which resources created by the user are placed
     *     singletonGroupId - The ID of the user's singleton group, i.e. the
     *       group containing just the user
     *     resource - The resource associated with the user
     */
    private function __construct(array $options)
    {
        $this->id = $options['id'];
        $this->username = $options['username'];
        $this->publicGroupId = $options['publicGroupId'];
        $this->privateGroupId = $options['privateGroupId'];
        $this->singletonGroupId = $options['singletonGroupId'];
        $this->resource = $options['resource'];
    }

    /**
     * Returns the ID of a record in the table AccessUser corresponding to
     * this CodeRage\Access\User
     *
     * @return int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Returns the username
     *
     * @return string
     */
    public function username()
    {
        return $this->username;
    }

    /**
     * Sets the password
     *
     * @param string $password The new password
     */
    public function setPassword($password)
    {
        $hash = Hash::generate($password);
        (new Db)->update('AccessUser', ['password' => $hash], $this->id);
    }

    /**
     * Returns the ID of this user's public group, i.e., the group into which
     * shared resources which the user has permission to view are placed
     *
     * @return int
     */
    public function publicGroupId()
    {
        return $this->publicGroupId;
    }

    /**
     * Returns this user's public group, i.e., the group into which shared
     * resources which the user has permission to view are placed
     *
     * @return CodeRage\User\Group
     */
    public function publicGroup()
    {
        return Group::load(['id' => $this->publicGroupId]);
    }

    /**
     * Returns the ID of this user's private group, i.e., the group into which
     * resources created by the user are placed
     *
     * @return int
     */
    public function privateGroupId()
    {
        return $this->privateGroupId;
    }

    /**
     * Returns this user's private group, i.e., the group into which resources
     * created by the user are placed
     *
     * @return CodeRage\User\Group
     */
    public function privateGroup()
    {
        return Group::load(['id' => $this->privateGroupId]);
    }

    /**
     * Returns the ID of this user's singleton group, i.e. the group containing
     * just the user
     *
     * @return int
     */
    public function singletonGroupId()
    {
        return $this->singletonGroupId;
    }

    /**
     * Returns this user's singleton group, i.e. the group containing just the
     * user
     *
     * @return CodeRage\User\Group
     */
    public function singletonGroup()
    {
        return Group::load(['id' => $this->singletonGroupId]);
    }


    /**
     * Returns the user ID of the most remote ancestor of this resource under
     * the owner-of relation, excluding the root user
     *
     * @return int
     */
    public function primaryAccount()
    {
        $primary = $this->resource->primaryOwner();
        return $primary == self::ROOT ?
            $this->id :
            $primary;
    }

    /**
     * Returns the resource associated with this CodeRage\Access\User
     *
     * @return CR_Access_Reource
     */
    public function resource()
    {
        return $this->resource;
    }

    /**
     * Creates a user
     *
     * @param string $username The username
     * @param string $password The password; defaults to a random string
     * @param mixed $owner The user that will own the new user, specified by
     *   ID, by name, or as an instance of CodeRage\Access\User
     *   by default, the owner will be the root user
     */
    public static function create(
        $username, $password = null, $owner = null)
    {
        // Validate and arguments
        Args::check($username, 'string', 'username');
        if (!preg_match(self::MATCH_NAME, $username))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid username: $username"
                ]);
        if (strlen($username) > self::NAME_MAX_LENGTH)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid username name '$username': username names " .
                        "may not exceed " . self::NAME_MAX_LENGTH .
                        " characters"
                ]);
        if ($password !== null) {
            Args::check($password, 'string', 'password');
        } else {
            $password = Random::string(self::RANDOM_PASSWORD_LENGTH);
        }
        $ownerId = null;
        if ($owner !== null) {
            Args::check($owner, 'int|string|CodeRage\Access\User', 'owner');
            $owner = is_int($owner) ?
                self::load(['id' => $owner]) :
                ( is_string($owner) ?
                      self::load(['name' => $owner]) :
                      $owner );
            $ownerId = $owner->id();
        } else {
            try {
                $owner = User::load(['id' => self::ROOT]);
            } catch (Error $ignore) {
                //Fall through
            }
            $ownerId = self::ROOT;
        }

        // Check is user exists
        $db = new Db;
        $sql =
            'SELECT COUNT(*) {i}
             FROM AccessUser
             WHERE username = %s';
        if ($db->fetchValue($sql, $username) > 0)
            throw new
                Error([
                    'status' => 'OBJECT_EXISTS',
                    'message' =>
                        "A user with username '$username' already exists"
                ]);

        // Create user
        $user = null;
        $db->beginTransaction();
        try {

            // Create resource
            $resource = Resource_::create('user', $ownerId);

            // Create singleton group
            $singleton =
                Group::create([
                    'name' => "singleton($username)",
                    'title' => "User $username Singleton Group"
                ]);
            $singleton->add($resource);

            // Create public group
            $public =
                Group::create([
                    'name' => "public($username)",
                    'title' => "User $username Public Group"
                ]);
            if ($owner !== null) {
                $group = Group::load(['id' => $owner->publicGroupId()]);
                $group->addChild($public);
            }
            Permission::grant([
                'permission' => 'view',
                'grantee' => $singleton,
                'target' => $public
            ]);

            // Create private group
            $private =
                Group::create([
                    'name' => "private($username)",
                    'title' => "User $username Private Group"
                ]);
            if ($owner !== null) {
                $group = Group::load(['id' => $owner->privateGroupId()]);
                $group->addChild($private);
            }
            Permission::grant([
                'permission' => Permission::UNIVERSAL,
                'grantee' => $singleton,
                'target' => $private
            ]);

            // Create AccessUser record
            $id =
                $db->insert(
                    'AccessUser',
                    [
                        'username' => $username,
                        'password' => Hash::generate($password),
                        'publicGroup' => $public->id(),
                        'privateGroup' => $private->id(),
                        'singletonGroup' => $singleton->id(),
                        'resource' => $resource->id()
                    ]
                );
            $user =
                new User([
                        'id' => $id,
                        'username' => $username,
                        'publicGroupId' => $public->id(),
                        'privateGroupId' => $private->id(),
                        'singletonGroupId' => $singleton->id(),
                        'resource' => $resource
                    ]);
        } catch (Throwable $e) {
            $db->rollback();
            if ($db->fetchValue($sql, $username) > 0)
                throw new
                    Error([
                        'status' => 'OBJECT_EXISTS',
                        'message' =>
                            "A user with username '$username' already exists"
                    ]);
            throw new
                Error([
                    'message' => 'Failed creating user',
                    'inner' => $e
                ]);
        }
        $db->commit();
        return $user;
    }

    /**
     * Returns the user with the given username or ID
     *
     * @param array $options The options array; must contain exactly one of the
     *   keys 'username' and 'id'
     * @return CodeRage\Access\User
     * @throws CodeRage\Error if no user matching the specified criterion exists
     */
    public static function load($options)
    {
        $opt = Args::uniqueKey($options, ['username', 'id']);
        Args::checkKey($options, 'username', 'string');
        if ( isset($options['username']) &&
             !preg_match(self::MATCH_NAME, $options['username']) )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid username: {$options['username']}"
                ]);
        }
        Args::checkKey($options, 'id', 'int');
        $db = new Db;
        $sql = $opt == 'username' ?
            'SELECT * FROM AccessUser WHERE username = %s' :
            'SELECT * FROM AccessUser WHERE RecordID = %i';
        $param = $options[$opt];
        $row = (new Db)->fetchFirstArray($sql, $param);
        if ($row === null) {
            $desc = $opt == 'username' ?
                "user[$param]" :
                ResourceId::encode($param, 'user');
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'message' => "No such user: $desc"
                ]);
        }
        $resource = Resource_::load(['id' => (int) $row['resource']]);
        return
            new User([
                    'id' => (int) $row['RecordID'],
                    'username' => $row['username'],
                    'publicGroupId' => (int) $row['publicGroup'],
                    'privateGroupId' => (int) $row['privateGroup'],
                    'singletonGroupId' => (int) $row['singletonGroup'],
                    'resource' => $resource
                ]);
    }

    public function nativeDataEncode(\CodeRage\Util\NativeDataEncoder $encoder)
    {
        return (object) ['id' => $this->id];
    }

    public function __toString()
    {
        return "user[$this->username]";
    }

    /**
     * The ID of a record in the table AccessUser corresponding to this
     * CodeRage\Access\User
     *
     * @var int
     */
    private $id;

    /**
     * The username
     *
     * @var string
     */
    private $username;

    /**
     * The ID of this user's public group, i.e., the group into which
     * shared resources which the user has permission to view are placed
     *
     * @var int
     */
    private $publicGroupId;

    /**
     * The ID of this user's private group, i.e., the group into which
     * resources created by the user are placed
     *
     * @var int
     */
    private $privateGroupId;

    /**
     * The ID of this user's singleton group, i.e. the group containing
     * just the user
     *
     * @var int
     */
    private $singletonGroupId;

    /**
     * The resource associated with this CodeRage\Access\User
     *
     * @var CodeRage\Access\Resource_
     */
    private $resource;
}

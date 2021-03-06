<?php
/**
 * LDAPPluginHook enables our checks as required
 *
 * PHP version 5
 *
 * @category LDAPPluginHook
 * @package  FOGProject
 * @author   Fernando Gietz <nah@nah.com>
 * @author   george1421 <nah@nah.com>
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * LDAPPluginHook enables our checks as required
 *
 * @category LDAPPluginHook
 * @package  FOGProject
 * @author   Fernando Gietz <nah@nah.com>
 * @author   george1421 <nah@nah.com>
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class LDAPPluginHook extends Hook
{
    public $name = 'LDAPPluginHook';
    public $description = 'LDAP Hook';
    public $author = 'Fernando Gietz';
    public $active = true;
    public $node = 'ldap';
    /**
     * Checks and creates users if they're valid
     *
     * @param mixed $arguments the item to adjust
     *
     * @throws Exception
     * @return void
     */
    public function checkAddUser($arguments)
    {
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        $user = trim($arguments['username']);
        $pass = trim($arguments['password']);
        /**
         * If this user is already signed in, return
         */
        if (self::$FOGUser->isValid()) {
            return;
        }
        $ldaps = self::getClass('LDAPManager')->find();
        self::$FOGUser
            ->set('name', $user)
            ->load('name');
        foreach ((array)$ldaps as &$ldap) {
            if (!$ldap->isValid()) {
                continue;
            }
            $access = $ldap->authLDAP($user, $pass);
            unset($ldap);
            switch ($access) {
                case false:
                    // Skip this
                    continue 2;
                case 2:
                    // This is an admin account, break the loop
                    self::$FOGUser
                        ->set('password', $pass)
                        ->set('type', 0)
                        ->save();
                    break 2;
                case 1:
                    // This is an unprivileged user account.
                    self::$FOGUser
                        ->set('password', $pass)
                        ->set('type', 1)
                        ->save();
                    break;
            }
        }
        unset($ldaps);
    }
}
$LDAPPluginHook = new LDAPPluginHook();
$HookManager->register('USER_LOGGING_IN', array($LDAPPluginHook, 'checkAddUser'));

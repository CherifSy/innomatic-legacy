<?php
/**
 * Innomatic
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  1999-2014 Innoteam Srl
 * @license    http://www.innomatic.org/license/   BSD License
 * @link       http://www.innomatic.org
 * @since      Class available since Release 6.4.0
*/

use \Innomatic\Core;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Innomatic\Domain\User;
use \Innomatic\Domain;
use \Shared\Wui;

class ProfilesPanelActions extends \Innomatic\Desktop\Panel\PanelActions
{
    protected $localeCatalog;
    public $status;
    public $javascript;

    public function __construct(\Innomatic\Desktop\Panel\PanelController $controller)
    {
        parent::__construct($controller);
    }

    public function beginHelper()
    {
        $this->localeCatalog = new LocaleCatalog(
            'innomatic::domain_profiles',
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );
    }

    public function endHelper()
    {
    }

    public function executeNewgroup($eventData)
    {
        $tempGroup = new Group();
        $groupData['groupname'] = $eventData['groupname'];
        $tempGroup->createGroup($groupData);
    }
    
    public function executeRengroup($eventData)
    {
        $tempGroup = new Group($eventData['gid']);
        $groupData['groupname'] = $eventData['groupname'];
        $tempGroup->editGroup($groupData);
    }
    
    public function executeRemovegroup($eventData)
    {
        if ($eventData['userstoo'] == 1)
            $deleteUsersToo = true;
        else
            $deleteUsersToo = false;
    
        $tempGroup = new Group($eventData['gid']);
        $tempGroup->removeGroup($deleteUsersToo);
    }
    
    public function executeAdduser($eventData)
    {
        if ($eventData['passworda'] == $eventData['passwordb']) {
            $tempUser = new User(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['id']);
            $userData['domainid'] = \Innomatic\Core\InnomaticContainer::instance(
                '\Innomatic\Core\InnomaticContainer'
            )->getCurrentDomain()->domaindata['id'];
            $userData['groupid'] = $eventData['groupid'];
            $userData['username'] = $eventData['username']
            . (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getEdition() == \Innomatic\Core\InnomaticContainer::EDITION_SAAS ? '@'
                .\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId() : '');
            $userData['password'] = $eventData['passworda'];
            $userData['fname'] = $eventData['fname'];
            $userData['lname'] = $eventData['lname'];
            $userData['email'] = $eventData['email'];
            $userData['otherdata'] = $eventData['other'];
    
            $tempUser->create($userData);
        }
    }
    
    public function executeEdituser($eventData)
    {
        $tempUser = new User(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['id'],
            $eventData['uid']
        );
        $userData['groupid'] = $eventData['groupid'];
        $userData['username'] = $eventData['username'];
        $userData['fname'] = $eventData['fname'];
        $userData['lname'] = $eventData['lname'];
        $userData['email'] = $eventData['email'];
        $userData['otherdata'] = $eventData['other'];
    
        if (!empty($eventData['oldpassword']) and !empty($eventData['passworda']) and !empty($eventData['passwordb'])) {
            if ($eventData['passworda'] == $eventData['passwordb']) {
                $userData['password'] = $eventData['passworda'];
            }
        }
    
        $tempUser->update($userData);
    }
    
    public function executeChpasswd($eventData)
    {
        $tempUser = new User(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['id'],
            $eventData['uid']
        );
        $tempUser->changePassword($eventData['password']);
    }
    
    public function executeChprofile($eventData)
    {
        $tempUser = new User(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['id'],
            $eventData['uid']
        );
        $userData['groupid'] = $eventData['profileid'];
        $tempUser->changeGroup($userData);
    }
    
    public function executeRemoveuser($eventData)
    {
        $tempUser = new User(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['id'],
            $eventData['uid']
        );
        $tempUser->remove();
    }
    
    public function executeEnablenode($eventData)
    {
        $tempPerm = new \Innomatic\Desktop\Auth\DesktopPanelAuthorizator(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
            $eventData['gid']
        );
        $tempPerm->enable($eventData['node'], $eventData['ntype']);
    }
    
    public function executeDisablenode($eventData)
    {
    
        $tempPerm = new \Innomatic\Desktop\Auth\DesktopPanelAuthorizator(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
            $eventData['gid']
        );
        $tempPerm->disable($eventData['node'], $eventData['ntype']);
    }
    
    public function executeSetmotd($eventData)
    {
        if (
        User::isAdminUser(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName(),
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId()
        )
        ) {    
            $domain = new \Innomatic\Domain\Domain(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId(),
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
            );
    
            $domain->setMotd($eventData['motd']);
            $this->status = $this->localeCatalog->getStr('motd_set.status');
            
            $this->setChanged();
            $this->notifyObservers('status');
        }
    }

    public static function ajaxSaveRolesPermissions($permissions) {
        // Build list of checked roles/permissions
        $permissions = explode(',', $permissions);
        $checkedPermissions = array();
        foreach ($permissions as $id => $permission) {
            $permission = str_replace('cbrole_', '', $permission);
            list($roleId, $permissionId) = explode('-', $permission);
            $checkedPermissions[$roleId][$permissionId] = true;
        }
        
        // Get list of all roles and permissions
        $rolesList = \Innomatic\Domain\User\Role::getAllRoles();
        $permissionsList = \Innomatic\Domain\User\Permission::getAllPermissions();
        
        // Check which permissions have been checked
        foreach ($rolesList as $roleId => $roleData) {
            $role = new \Innomatic\Domain\User\Role($roleId);
            
            foreach ($permissionsList as $permissionId => $permissionData) {
                if (isset($checkedPermissions[$roleId][$permissionId])) {
                    $role->assignPermission($permissionId);
                } else {
                    $role->unassignPermission($permissionId);
                }
            }
        }
        
        $html = WuiXml::getContentFromXml('', \ProfilesPanelController::getRolesPermissionsXml());
         
        $objResponse = new XajaxResponse();
        $objResponse->addAssign("roleslist", "innerHTML", $html);
         
        return $objResponse;
    }
}

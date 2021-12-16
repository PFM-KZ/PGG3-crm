<?php

namespace GCRM\CRMBundle\Configuration;

use Doctrine\ORM\EntityManager;
use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheManager;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuConfigPass as BaseClass;
use GCRM\CRMBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MenuConfigPass extends BaseClass
{
    private $tokenStorage;

    private $cacheManager;

    public function __construct(CacheManager $cacheManager, TokenStorageInterface $tokenStorage)
    {
        $this->cacheManager = $cacheManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function process(array $backendConfig)
    {
        $backendConfig = parent::process($backendConfig);
        $this->cacheManager->delete('processed_config');

        $menu = $backendConfig['design']['menu'];
        $token = $this->tokenStorage->getToken();

        $userRoles = [];
        if ($token && is_object($token->getUser())) {
            $userRoles = $this->tokenStorage->getToken()->getUser()->getRoles();
        }

        $filteredMenu = [];

        // menu parents
        foreach ($menu as $menuItem) {
            $permissions = isset($menuItem['permissions']) ? $menuItem['permissions'] : null;

            if (!is_array($permissions)) {
                $filteredMenu[] = $menuItem;
            } else {
                $passed = true;
                foreach ($permissions as $permission) {
                    if (!in_array($permission, $userRoles)) {
                        $passed = false;
                        break;
                    }
                }
                if ($passed) {
                    $filteredMenu[] = $menuItem;
                }
            }
        }

        // menu childrens
        foreach ($filteredMenu as $key => $menuItem) {
            $childrens = $menuItem['children'];
            $filteredChildrens = [];

            foreach ($childrens as $childrenKey => $children) {
                $permissions = isset($children['permissions']) ? $children['permissions'] : null;
                if (!is_array($permissions)) {
                    $filteredChildrens[] = $children;
                } else {
                    $passed = true;
                    foreach ($permissions as $permission) {
                        if (!in_array($permission, $userRoles)) {
                            $passed = false;
                            break;
                        }
                    }
                    if ($passed) {
                        $filteredChildrens[] = $children;
                    }
                }
            }

            $filteredMenu[$key]['children'] = $filteredChildrens;
        }

        $filteredMenu = $this->resetIndexes($filteredMenu);

        $backendConfig['design']['menu'] = $filteredMenu;

        return $backendConfig;
    }

    private function resetIndexes($filteredMenu)
    {
        $mainIndex = 0;
        for ($i = 0; $i < count($filteredMenu); $i++) {
            $filteredMenu[$i]['menu_index'] = $mainIndex;

            if (count($filteredMenu[$i]['children'])) {
                $subIndex = 0;
                for ($a = 0; $a < count($filteredMenu[$i]['children']); $a++) {
                    $filteredMenu[$i]['children'][$a]['menu_index'] = $mainIndex;
                    $filteredMenu[$i]['children'][$a]['submenu_index'] = $subIndex;
                    $subIndex++;
                }
            }

            $mainIndex++;
        }

        return $filteredMenu;
    }
}
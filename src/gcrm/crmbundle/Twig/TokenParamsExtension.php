<?php

namespace GCRM\CRMBundle\Twig;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Extension;

class TokenParamsExtension extends Twig_Extension
{
    private $container;
    private $statusDepartments;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->container = $container;
        $this->statusDepartments = $em->getRepository('GCRMCRMBundle:StatusDepartment')->findAll();
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('tokenParams', array($this, 'tokenParams')),
        );
    }

    public function tokenParams($params)
    {
//        return [];
//
        $result = [];
        foreach ($params as $key => $value) {
//            if ($key == 'lsBranch') { // disabled functionality
//                // get current logged in user branch
//                /** @var User $user */
//                $user = $this->container->get('security.token_storage')->getToken()->getUser();
//                $branch = $user->getBranch();
//                if ($branch) {
//                    $result[$key] = $branch->getId();
//                }
//            } else
            if ($key == 'lsStatusDepartment') {
                // changes code for id
                /** @var StatusDepartment $statusDepartment */
                foreach ($this->statusDepartments as $statusDepartment) {
                    if ($statusDepartment->getCode() == $value) {
                        $result[$key] = $statusDepartment->getId();
                        break;
                    }
                }

            }
        }

        return $result;
    }
}
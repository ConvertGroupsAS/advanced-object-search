<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace AdvancedObjectSearchBundle\EventListener;


use AdvancedObjectSearchBundle\Service;
use Pimcore\Event\Model\Object\ClassDefinitionEvent;
use Pimcore\Event\Model\ObjectEvent;
use Pimcore\Event\System\MaintenanceEvent;
use Pimcore\Logger;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Schedule\Maintenance\Job;

class IndexUpdateListener
{
    /**
     * @var Service
     */
    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function updateObject(ObjectEvent $event)
    {
        $inheritanceBackup = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(true);

        $object = $event->getObject();
        if($object instanceof Concrete) {
            $this->service->doUpdateIndexData($object);
        }

        AbstractObject::setGetInheritedValues($inheritanceBackup);
    }

    public function deleteObject(ObjectEvent $event)
    {
        $object = $event->getObject();
        if($object instanceof Concrete) {
            $this->service->doDeleteFromIndex($object);
        }
    }

    public function updateMapping(ClassDefinitionEvent $event) {
        $classDefinition = $event->getClassDefinition();
        $this->service->updateMapping($classDefinition);
    }

    public function deleteIndex(ClassDefinitionEvent $event) {
        $classDefinition = $event->getClassDefinition();
        try {
            $this->service->deleteIndex($classDefinition);
        } catch (\Exception $e) {
            Logger::err($e);
        }
    }

    public function registerMaintenanceJob(MaintenanceEvent $maintenanceEvent) {
        $maintenanceEvent->getManager()->registerJob(new Job(get_class($this), $this, "maintenance"));
    }

    public function maintenance() {
        $this->service->processUpdateQueue(500);
    }

}

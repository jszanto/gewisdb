<?php

namespace Import\Service;

use Application\Service\AbstractService;

class Member extends AbstractService
{

    /**
     * Get members.
     *
     * @return array
     */
    public function getMembers()
    {
        return $this->getQuery()->fetchMembers();
    }

    /**
     * Get the query object.
     */
    public function getQuery()
    {
        return $this->getServiceManager()->get('import_database_query');
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
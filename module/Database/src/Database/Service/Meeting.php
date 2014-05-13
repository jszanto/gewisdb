<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Database\Model\Meeting as MeetingModel;
use Database\Model\Decision;
use Database\Model\SubDecision;

use Zend\Stdlib\Hydrator\ObjectProperty as ObjectPropertyHydrator;

class Meeting extends AbstractService
{

    /**
     * Get a meeting.
     *
     * @param string $type
     * @param int $number
     *
     * @return MeetingModel
     */
    public function getMeeting($type, $number)
    {
        return $this->getMeetingMapper()->find($type, $number);
    }

    /**
     * Get all meetings.
     *
     * @todo pagination
     *
     * @return array All meetings.
     */
    public function getAllMeetings()
    {
        $mapper = $this->getMeetingMapper();

        return $mapper->findAll();
    }

    /**
     * Find decisions by meetings.
     *
     * @param array $meetings
     *
     * @return array Of decisions.
     */
    public function getDecisionsByMeetings($meetings)
    {
        $mapper = $this->getMeetingMapper();

        return $mapper->findDecisionsByMeetings($meetings);
    }

    /**
     * Export decisions.
     *
     * @param array $data
     *
     * @return array
     */
    public function export($data)
    {
        $form = $this->getExportForm();

        $form->setData($data);

        if (!$form->isValid()) {
            var_dump('booh');
            return null;
        }

        // extract the meetings
        $data = $form->getData();
        $meetings = array();
        foreach ($data['meetings'] as $meeting) {
            $meeting = explode('-', $meeting);
            $meetings[] = array(
                'type' => $meeting[0],
                'number' => $meeting[1]
            );
        }

        // find meeting data
        return $this->getDecisionsByMeetings($meetings);
    }

    /**
     * Other decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function otherDecision($data)
    {
        $form = $this->getOtherForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'other',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('decision' => $decision));
        $this->getMeetingMapper()->persist($decision->getMeeting());
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('decision' => $decision));


        return array(
            'type' => 'other',
            'decision' => $decision
        );
    }

    /**
     * Abolish decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function abolishDecision($data)
    {
        $form = $this->getAbolishForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'abolish',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('decision' => $decision));
        $this->getMeetingMapper()->persist($decision->getMeeting());
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('decision' => $decision));

        return array(
            'type' => 'foundation',
            'decision' => $decision
        );
    }

    /**
     * Install decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function installDecision($data)
    {
        $form = $this->getInstallForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'install',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('decision' => $decision));
        $this->getMeetingMapper()->persist($decision->getMeeting());
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('decision' => $decision));

        return array(
            'type' => 'install',
            'decision' => $decision
        );
    }

    /**
     * Foundation decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function foundationDecision($data)
    {
        $form = $this->getFoundationForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'foundation',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('decision' => $decision));
        $this->getMeetingMapper()->persist($decision->getMeeting());
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('decision' => $decision));

        return array(
            'type' => 'foundation',
            'decision' => $decision
        );
    }

    /**
     * Budget decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function budgetDecision($data)
    {
        $form = $this->getBudgetForm();

        // use hack to make sure we do not have validators for these fields
        $approveChain = $form->getInputFilter()->get('approve')->getValidatorChain();
        $refObj = new \ReflectionObject($approveChain);
        $refProp = $refObj->getProperty('validators');
        $refProp->setAccessible(true);
        $refProp->setValue($approveChain, array());

        $changesChain = $form->getInputFilter()->get('changes')->getValidatorChain();
        $refObj = new \ReflectionObject($changesChain);
        $refProp = $refObj->getProperty('validators');
        $refProp->setAccessible(true);
        $refProp->setValue($changesChain, array());


        $form->setData($data);

        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'budget',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('decision' => $decision));
        $this->getMeetingMapper()->persist($decision->getMeeting());
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('decision' => $decision));

        return array(
            'type' => 'budget',
            'decision' => $decision
        );
    }

    /**
     * Create a meeting.
     *
     * @param array $data Meeting creation data.
     *
     * @return boolean If creation was succesfull
     */
    public function createMeeting($data)
    {
        $form = $this->getCreateMeetingForm();
        $form->bind(new MeetingModel());
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $meeting = $form->getData();
        $mapper = $this->getMeetingMapper();

        if ($mapper->isManaged($meeting)) {
            // meeting is already in the database
            $form->setMessages(array(
                'number' => array(
                    'Deze vergadering bestaat al'
                )
            ));
            return false;
        }

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('meeting' => $meeting));
        $mapper->persist($meeting);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('meeting' => $meeting));

        return true;
    }

    /**
     * Search for organs by name.
     *
     * @param string $query
     *
     * @return array organ (decisions)
     */
    public function organSearch($query)
    {
        return $this->getOrganMapper()->organSearch($query);
    }

    /**
     * Get the foundation of an organ.
     *
     * @param string $type
     * @param string $meetingNumber
     * @param string $decisionPoint
     * @param string $decisionNumber
     * @param string $subdecisionNumber
     *
     * @return \Database\Model\SubDecision\Foundation
     */
    public function findFoundation($type, $meetingNumber, $decisionPoint, $decisionNumber, $subdecisionNumber)
    {
        return $this->getOrganMapper()->find(
            $type, $meetingNumber, $decisionPoint, $decisionNumber, $subdecisionNumber
        );
    }

    /**
     * Get the create meeting form.
     *
     * @return \Database\Form\CreateMeeting
     */
    public function getCreateMeetingForm()
    {
        return $this->getServiceManager()->get('database_form_createmeeting');
    }

    /**
     * Get install form.
     *
     * @return \Database\Form\Install
     */
    public function getInstallForm()
    {
        return $this->getServiceManager()->get('database_form_install');
    }

    /**
     * Get abolish form.
     *
     * @return \Database\Form\Abolish
     */
    public function getAbolishForm()
    {
        return $this->getServiceManager()->get('database_form_abolish');
    }

    /**
     * Get foundation form.
     *
     * @return \Database\Form\Foundation
     */
    public function getFoundationForm()
    {
        return $this->getServiceManager()->get('database_form_foundation');
    }

    /**
     * Get budget form.
     *
     * @return \Database\Form\Budget
     */
    public function getBudgetForm()
    {
        return $this->getServiceManager()->get('database_form_budget');
    }

    /**
     * Get other form.
     *
     * @return \Database\Form\Other
     */
    public function getOtherForm()
    {
        return $this->getServiceManager()->get('database_form_other');
    }

    /**
     * Get the export form.
     *
     * @return \Database\Form\Export
     */
    public function getExportForm()
    {
        return $this->getServiceManager()->get('database_form_export');
    }

    /**
     * Get the meeting mapper.
     *
     * @return \Database\Mapper\Meeting
     */
    public function getMeetingMapper()
    {
        return $this->getServiceManager()->get('database_mapper_meeting');
    }

    /**
     * Get the organ mapper.
     *
     * @return \Database\Mapper\Organ
     */
    public function getOrganMapper()
    {
        return $this->getServiceManager()->get('database_mapper_organ');
    }
}

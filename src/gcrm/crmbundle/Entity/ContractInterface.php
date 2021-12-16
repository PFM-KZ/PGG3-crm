<?php

namespace GCRM\CRMBundle\Entity;

interface ContractInterface
{
    public function getContractNumber();
    public function setContractNumber($contractNumber);

    public function getSignDate();
    public function setSignDate($signDate);

    public function getUser();
    public function setUser(User $user);

    public function getIsReturned();
    public function setIsReturned($isReturned);

    public function getStatusDepartment();
    public function setStatusDepartment($statusDepartment);

    public function getIsDownloaded();
    public function setIsDownloaded($isDownloaded);

    public function getPackageToSend();
    public function setPackageToSend($packageToSend);

    public function setIsOnPackageList($isOnPackageList);
    public function getIsOnPackageList();

    public function setSalesRepresentative($agent);
    public function getSalesRepresentative();

    public function getStatusAuthorization();
    public function setStatusAuthorization($statusAuthorization);
    public function getCommentAuthorization();
    public function setCommentAuthorization($commentAuthorization);

    public function getStatusVerification();
    public function setStatusVerification($statusVerification);
    public function getCommentVerification();
    public function setCommentVerification($commentVerification);

    public function getStatusAdministration();
    public function setStatusAdministration($statusAdministration);
    public function getCommentAdministration();
    public function setCommentAdministration($commentAdministration);

    public function getStatusControl();
    public function setStatusControl($statusControl);
    public function getCommentControl();
    public function setCommentControl($commentControl);

    public function getStatusProcess();
    public function setStatusProcess($statusControl);
    public function getCommentProcess();
    public function setCommentProcess($commentControl);

    public function getStatusFinances();
    public function setStatusFinances($statusFinances);
    public function getCommentFinances();
    public function setCommentFinances($commentFinances);

    public function addRecordingAttachment($recordingAttachment);
    public function removeRecordingAttachment($recordingAttachment);
    public function getRecordingAttachments();
    public function setRecordingAttachments($recordingAttachments);

    public function addContractAttachment($contractAttachment);
    public function removeContractAttachment($contractAttachment);
    public function getContractAttachments();
    public function setContractAttachments($contractAttachments);

    public function getIsResignation();
    public function setIsResignation($isResignation);

    public function getIsBrokenContract();
    public function setIsBrokenContract($isBrokenContract);

    public function getType();
    public function setType($type);

}


<?php

namespace MKDF\Keys\Feature;

use MKDF\Core\Service\AccountFeatureInterface;

class AccountKeysFeature implements AccountFeatureInterface
{
    private $active = false;

    public function getController() {
        return \MKDFKeys\Controller\KeyController::class;
    }
    public function getViewAction(){
        return 'index';
    }
    public function getEditAction(){
        return 'index';
    }
    public function getViewHref(){
        return '/key';
    }
    public function getEditHref(){
        return '/key';
    }
    public function hasFeature(){
        // They all have this one
        return true;
    }
    public function getLabel(){
        return 'My keys';
    }
    public function isActive(){
        return $this->active;
    }
    public function setActive($bool){
        $this->active = !!$bool;
    }

}
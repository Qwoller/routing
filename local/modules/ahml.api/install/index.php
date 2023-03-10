<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class ahml_api extends CModule {
    public function __construct() {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_ID = 'ahml.api';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('AHML_API_MODULE_NAME');
        $this->PARTNER_NAME = Loc::getMessage('AHML_API_PARTNER_NAME');
    }

    public function DoInstall() {
        if($this->isVersionD7()){
            ModuleManager::registerModule($this->MODULE_ID);
        }
    }

    public function DoUninstall() {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    private function isVersionD7() : bool {
        return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
    }
}
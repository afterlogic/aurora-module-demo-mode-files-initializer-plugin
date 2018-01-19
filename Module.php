<?php
/**
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\DemoModeFilesInitializerPlugin;

/**
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	protected $oDemoModePluginDecorator = null;
	
	protected $oFilesDecorator = null;
	
	public function init() 
	{
		$oDemoModePluginDecorator = \Aurora\Modules\DemoModePlugin\Module::Decorator();
		$oFilesDecorator = \Aurora\Modules\Files\Module::Decorator();
		
		if (empty($oDemoModePluginDecorator) || empty($oFilesDecorator))
		{
			return;
		}
		
		$this->oDemoModePluginDecorator = $oDemoModePluginDecorator;
		$this->oFilesDecorator = $oFilesDecorator;
		
		$this->subscribeEvent('Core::Login::after', array($this, 'onAfterLogin'), 10);
	}
	
	public function onAfterLogin(&$aArgs, &$mResult)
	{
		$oSettings = $this->oDemoModePluginDecorator->GetSettings();
		$bDemoUser = isset($oSettings['IsDemoUser']) && !!$oSettings['IsDemoUser'];
		
		if ($bDemoUser)
		{
			$this->populateData();
		}
	}
	
	public function populateData()
	{
		$sType = 'personal';
		$sPath = '/';
		$iErrors = 0;
		$sResourceDir = __Dir__.'/content/';
		
		//workaround for api get worked
		if (isset($mResult['AuthToken']))
		{
			\Aurora\System\Api::getAuthenticatedUserId($mResult['AuthToken']);
		}
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();

		$aFiles = scandir($sResourceDir);
		foreach ($aFiles as $sFileName)
		{
			if ($sFileName !== '.' && $sFileName !== '..')
			{
				$aUploadData = array(
					'name' => $sFileName,
					'tmp_name' => fopen($sResourceDir.$sFileName, 'r'),
					'size' => '0.1'
				);
				
				if (!$this->oFilesDecorator->UploadFile($oUser->EntityId, $sType, $sPath, $aUploadData)) 
				{
					$iErrors++;
				}
			}
		}
		
		return $iErrors > 0;
	}
}

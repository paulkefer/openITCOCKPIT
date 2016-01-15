<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

/**
 * @property ChangelogComponent                       $Changelog
 * @property CustomValidationErrorsComponent          $CustomValidationErrors
 * @property Servicetemplate                          $Servicetemplate
 * @property Timeperiod                               $Timeperiod
 * @property Command                                  $Command
 * @property Contact                                  $Contact
 * @property Contactgroup                             $Contactgroup
 * @property Container                                $Container
 * @property Commandargument                          $Commandargument
 * @property Customvariable                           $Customvariable
 * @property Servicetemplatecommandargumentvalue      $Servicetemplatecommandargumentvalue
 * @property Servicetemplateeventcommandargumentvalue $Servicetemplateeventcommandargumentvalue
 */
class ServicetemplatesController extends AppController{
	public $layout = 'Admin.default';
	public $components = [
		'Paginator',
		'ListFilter.ListFilter',
		'CustomValidationErrors',
		'AdditionalLinks',
	];
	public $helpers = [
		'ListFilter.ListFilter',
		'CustomValidationErrors',
		'CustomVariables',
	];
	public $listFilters = [
		'index' => [
			'fields' => [
				'Servicetemplate.name' => array('label' => 'Templatename', 'searchType' => 'wildcard'),
			],
		],
	];
	public $uses = [
		'Servicetemplate',
		'Timeperiod',
		'Command',
		'Contact',
		'Contactgroup',
		'Container',
		'Commandargument',
		'Customvariable',
		'Servicetemplatecommandargumentvalue',
		'Servicetemplateeventcommandargumentvalue',
	];

	public function index(){
		$options = [
			'recursive' => -1,
			'order' => [
				'Servicetemplate.name' => 'asc'
			],
			'conditions' => [
				//'Servicetemplate.servicetemplatetype_id' => GENERIC_SERVICE,
				'Container.id' => $this->MY_RIGHTS
			],
			'fields' => [
				'Servicetemplate.id',
				'Servicetemplate.uuid',
				'Servicetemplate.name',
				'Servicetemplate.container_id',
				'Servicetemplate.description',
				'Container.*',
			],
			'contain' => [
				'Container'
			]
		];

		if($this->isApiRequest()){
			unset($options['limit']);
			$all_servicetemplates = $this->Servicetemplate->find('all', $options);
		}else{
			$this->Paginator->settings = Hash::merge($options, $this->Paginator->settings);
			$all_servicetemplates = $this->Paginator->paginate();
		}

		$this->set(compact(['all_servicetemplates']));
		$this->set('_serialize', ['all_servicetemplates']);

		if(isset($this->request->data['Filter']) && $this->request->data['Filter'] !== null){
			$this->set('isFilter', true);
		}else{
			$this->set('isFilter', false);
		}
	}

	public function view($id = null){
		if(!$this->isApiRequest()){
			throw new MethodNotAllowedException();
		}
		if(!$this->Servicetemplate->exists($id)){
			throw new NotFoundException(__('404 Not Found'));
		}
		$servicetemplate = $this->Servicetemplate->find('first', [
			'recursive' => -1,
			'contain' => [
				'Container',
				'CheckPeriod',
				'NotifyPeriod',
				'CheckCommand',
				'EventhandlerCommand',
				'Customvariable',
				'Servicetemplatecommandargumentvalue',
				'Servicetemplateeventcommandargumentvalue',
				'Contactgroup',
				'Contact'
			],
			'conditions' => [
				'Servicetemplate.id' => $id
			]
		]);

		if(!$this->allowedByContainerId(Hash::extract($servicetemplate, 'Container.id'))){
			$this->render403();
			return;
		}

		$this->set(compact(['servicetemplate']));
		$this->set('_serialize', ['servicetemplate']);
	}

	public function edit($id = null, $servicetemplatetype_id = null){
		$userId = $this->Auth->user('id');
		$this->Servicetemplate->id = $id;
		if(!$this->Servicetemplate->exists()){
			throw new NotFoundException(__('Invalid servicetemplate'));
		}

		$serviceTemplate = $this->Servicetemplate->find('first', [
			'conditions' => [
				'Servicetemplate.id' => $id,
			],
			'contain' => [
				'Contactgroup' => ['Container'],
				'CheckCommand',
				'EventhandlerCommand',
				'Container',
				'Customvariable',
				'NotifyPeriod',
				'CheckPeriod',
				'Contact',
				'Servicetemplatecommandargumentvalue' => ['Commandargument'],
				'Servicetemplateeventcommandargumentvalue' => ['Commandargument'],
			]
		]);
		if(!$this->allowedByContainerId(Hash::extract($serviceTemplate, 'Container.id'))){
			$this->render403();
			return;
		}

		$servicetemplate_for_changelog = $serviceTemplate;


		$commands = $this->Command->serviceCommands('list');
		$eventHandlers = $this->Command->eventhandlerCommands('list');
		if($this->hasRootPrivileges === true){
			$containers = $this->Tree->easyPath($this->MY_RIGHTS, OBJECT_SERVICETEMPLATE, [], $this->hasRootPrivileges, [CT_SERVICETEMPLATEGROUP]);
		}else{
			$containers = $this->Tree->easyPath($this->getWriteContainers(), OBJECT_SERVICETEMPLATE, [], $this->hasRootPrivileges, [CT_SERVICETEMPLATEGROUP]);
		}
		// Data to refill form
		if($this->request->is('post') || $this->request->is('put')){
			$containerId = $this->request->data('Servicetemplate.container_id');
		}else{
			$containerId = $serviceTemplate['Servicetemplate']['container_id'];
		}

		$containerIds = $this->Tree->resolveChildrenOfContainerIds($containerId);

		$timeperiods = $this->Timeperiod->timeperiodsByContainerId($containerIds, 'list');
		$contacts = $this->Contact->contactsByContainerId($containerIds, 'list');
		$contactGroups = $this->Contactgroup->contactgroupsByContainerId($containerIds, 'list');

		//Fehlende bzw. neu angelegte CommandArgummente ermitteln und anzeigen
		$commandarguments = $this->Commandargument->find('all', [
			'recursive' => -1,
			'conditions' => [
				'Commandargument.command_id' => $serviceTemplate['CheckCommand']['id']
			]
		]);

		//Fix that we dont lose any unsaved host macros, because of vaildation error
		if(isset($this->request->data['Customvariable'])){
			$serviceTemplate['Customvariable'] = Hash::merge($serviceTemplate['Customvariable'], $this->request->data['Customvariable']);
		}
		$this->Frontend->set('data_placeholder', __('Please choose a contact'));
		$this->Frontend->set('data_placeholder_empty', __('No entries found'));
		$this->Frontend->setJson('lang_minutes', __('minutes'));
		$this->Frontend->setJson('lang_seconds', __('seconds'));
		$this->Frontend->setJson('lang_and', __('and'));
		$this->Frontend->setJson('servicetemplate_id', $serviceTemplate['Servicetemplate']['id']);

		/*if(!empty($hosttemplate['Customvariable'])){
			$this->Frontend->setJson('customVariablesCount', Set::apply('{n}.id', $hosttemplate['Customvariable'], 'max'));
		}else{
			$this->Frontend->setJson('customVariablesCount', 0);
			// <-- required for working javascript with customvariables component!
		}*/

		$this->Frontend->setJson('customVariablesCount', sizeof($serviceTemplate['Customvariable']));
		// <-- required for working javascript with customvariables component!

		$this->set('back_url', $this->referer());

		if($this->request->is('post') || $this->request->is('put')){
			$ext_data_for_changelog = [
				'Contact' => [],
				'Contactgroup' => [],
				'EventhandlerCommand' => []
			];
			if($this->request->data('Servicetemplate.Contact')){
				if($contactsForChangelog = $this->Contact->find('list', [
					'conditions' => [
						'Contact.id' => $this->request->data['Servicetemplate']['Contact']
					]
				])){
					foreach($contactsForChangelog as $contactId => $contactName){
						$ext_data_for_changelog['Contact'][] = [
							'id' => $contactId,
							'name' => $contactName
						];
					}
					unset($contactsForChangelog);
				}
			}
			if($this->request->data('Servicetemplate.Contactgroup')){
				if($contactgroupsForChangelog = $this->Contactgroup->find('all', [
					'recursive' => -1,
					'contain' => [
						'Container' => [
							'fields' => [
								'Container.name'
							]
						]
					],
					'fields' => [
						'Contactgroup.id'
					],
					'conditions' => [
						'Contactgroup.id' => $this->request->data['Servicetemplate']['Contactgroup']
					]
				])){
					foreach($contactgroupsForChangelog as $contactgroupData){
						$ext_data_for_changelog['Contactgroup'][] = [
							'id' => $contactgroupData['Contactgroup']['id'],
							'name' => $contactgroupData['Container']['name']
						];
					}
					unset($contactgroupsForChangelog);
				}
			}
			if($this->request->data('Servicetemplate.notify_period_id')){
				if($timeperiodsForChangelog = $this->Timeperiod->find('list', [
					'conditions' => [
						'Timeperiod.id' => $this->request->data['Servicetemplate']['notify_period_id']
					]
				])){
					foreach($timeperiodsForChangelog as $timeperiodId => $timeperiodName){
						$ext_data_for_changelog['NotifyPeriod'] = [
							'id' => $timeperiodId,
							'name' => $timeperiodName
						];
					}
					unset($timeperiodsForChangelog);
				}
			}
			if($this->request->data('Servicetemplate.check_period_id')){
				if($timeperiodsForChangelog = $this->Timeperiod->find('list', [
					'conditions' => [
						'Timeperiod.id' => $this->request->data['Servicetemplate']['check_period_id']
					]
				])){
					foreach($timeperiodsForChangelog as $timeperiodId => $timeperiodName){
						$ext_data_for_changelog['CheckPeriod'] = [
							'id' => $timeperiodId,
							'name' => $timeperiodName
						];
					}
					unset($timeperiodsForChangelog);
				}
			}
			if($this->request->data('Servicetemplate.command_id')){
				if($commandsForChangelog = $this->Command->find('list', [
					'conditions' => [
						'Command.id' => $this->request->data['Servicetemplate']['command_id']
					]
				])){
					foreach($commandsForChangelog as $commandId => $commandName){
						$ext_data_for_changelog['CheckCommand'] = [
							'id' => $commandId,
							'name' => $commandName
						];
					}
					unset($commandsForChangelog);
				}
			}

			if($this->request->data('Servicetemplate.eventhandler_command_id') && $this->request->data('Servicetemplate.eventhandler_command_id')>0){
				if($eventHandlerCommandsForChangelog = $this->Command->find('list', [
					'conditions' => [
						'Command.id' => $this->request->data['Servicetemplate']['eventhandler_command_id']
					]
				])){
					foreach($eventHandlerCommandsForChangelog as $eventHandlerCommandId => $eventHandlerCommandName){
						$ext_data_for_changelog['EventhandlerCommand'] = [
							'id' => $eventHandlerCommandId,
							'name' => $eventHandlerCommandName
						];
					}
					unset($eventHandlerCommandsForChangelog);
				}
			}

			//Checks if the user deletes a customvariable/macro over the trash icon
			if(!isset($this->request->data['Customvariable'])){
				$this->request->data['Customvariable'] = [];
			}

			//Delete Command argument values
			//Fetching all commandargument_id of the command arguments out of database:
			$commandargumentIdsOfDatabase = Hash::extract($serviceTemplate['Servicetemplatecommandargumentvalue'], '{n}.commandargument_id');
			//Fetching all commandargument_id out of $this->request-data
			$commandargumentIdsOfRequest = [];
			if(isset($this->request->data['Servicetemplatecommandargumentvalue'])){
				$commandargumentIdsOfRequest = Hash::extract($this->request->data['Servicetemplatecommandargumentvalue'], '{n}.commandargument_id');
			}
			// Checking if the user deleted this argument or changed the command and if we need to delete it out of the database
			$this->loadModel('Servicetemplatecommandargumentvalue');
			foreach($commandargumentIdsOfDatabase as $commandargumentId){
				if(!in_array($commandargumentId, $commandargumentIdsOfRequest)){
					// Deleteing the parameter of the argument out of database (sorry ugly php 5.4+ syntax - check twice before modify)
					$this->Servicetemplatecommandargumentvalue->delete(
						$this->Servicetemplatecommandargumentvalue->find('first', [
							'conditions' => [
								'servicetemplate_id' => $serviceTemplate['Servicetemplate']['id'],
								'commandargument_id' => $commandargumentId
							]
						])
						['Servicetemplatecommandargumentvalue']
					);
				}
			}

			//Delete Command argument values
			//Fetching all commandargument_id of the command arguments out of database:
			$commandargumentIdsOfDatabase = Hash::extract($serviceTemplate['Servicetemplateeventcommandargumentvalue'], '{n}.commandargument_id');
			//Fetching all commandargument_id out of $this->request-data
			$commandargumentIdsOfRequest = [];
			if(isset($this->request->data['Servicetemplateeventcommandargumentvalue'])){
				$commandargumentIdsOfRequest = Hash::extract($this->request->data['Servicetemplateeventcommandargumentvalue'], '{n}.commandargument_id');
			}
			// Checking if the user deleted this argument or changed the command and if we need to delete it out of the database
			$this->loadModel('Servicetemplateeventcommandargumentvalue');
			foreach($commandargumentIdsOfDatabase as $commandargumentId){
				if(!in_array($commandargumentId, $commandargumentIdsOfRequest)){
					// Deleteing the parameter of the argument out of database (sorry ugly php 5.4+ syntax - check twice before modify)
					$this->Servicetemplateeventcommandargumentvalue->delete(
						$this->Servicetemplateeventcommandargumentvalue->find('first', [
							'conditions' => [
								'servicetemplate_id' => $serviceTemplate['Servicetemplate']['id'],
								'commandargument_id' => $commandargumentId
							]
						])
						['Servicetemplateeventcommandargumentvalue']
					);
				}
			}

			if($servicetemplatetype_id !== null && is_numeric($servicetemplatetype_id)){
				$this->request->data['Servicetemplate']['servicetemplatetype_id'] = $servicetemplatetype_id;
			}
			$isJson = $this->request->ext == 'json';

			if($this->request->data('Servicetemplate.Contact') === ''){
				$this->request->data['Servicetemplate']['Contact'] = [];
			}
			if($this->request->data('Servicetemplate.Contactgroup') === ''){
				$this->request->data['Servicetemplate']['Contactgroup'] = [];
			}
			$this->request->data['Contact']['Contact'] = $this->request->data('Servicetemplate.Contact');
			$this->request->data['Contactgroup']['Contactgroup'] = $this->request->data('Servicetemplate.Contactgroup');
			// Save everything including custom variables
			if($this->Servicetemplate->saveAll($this->request->data)){
				$requestData = array_merge($this->request->data, $ext_data_for_changelog);
				$changelog_data = $this->Changelog->parseDataForChangelog(
					$this->params['action'],
					$this->params['controller'],
					$id,
					OBJECT_SERVICETEMPLATE,
					$this->request->data['Servicetemplate']['container_id'],
					$userId,
					$this->request->data['Servicetemplate']['name'],
					$requestData,
					$servicetemplate_for_changelog
				);
				if($changelog_data){
					CakeLog::write('log', serialize($changelog_data));
				}

				if($isJson){
					$this->serializeId();
					return;
				}

				$this->setFlash(__('<a href="/services/edit/%s">Servicetemplate</a> successfully saved.', $this->Servicetemplate->id));
				$this->redirect(array('action' => 'index'));
			}else{
				if($isJson){
					$this->serializeErrorMessage();
					return;
				}

				$this->setFlash(__('Could not save data.'), false);
				$this->CustomValidationErrors->loadModel($this->Servicetemplate);
				$this->CustomValidationErrors->customFields([
					'notification_interval',
					'check_interval',
					'retry_interval',
					'notify_on_recovery',
					'flap_detection_on_up'
				]);
				$this->CustomValidationErrors->fetchErrors();

				foreach($this->Customvariable->validationErrors as $customVariableValidationError){
					if(isset($customVariableValidationError['name'])){
						$this->set('customVariableValidationError', current($customVariableValidationError['name']));
					}
				}

				foreach($this->Customvariable->validationErrors as $customVariableValidationError){
					if(isset($customVariableValidationError['value'])){
						$this->set('customVariableValidationErrorValue', current($customVariableValidationError['value']));
					}

					// Refill data that was loaded by Ajax
					if($this->Container->exists($this->request->data('Servicetemplate.container_id'))){
						$containerIds = $this->request->data('Servicetemplate.container_id');
						$containerIds = $this->Tree->resolveChildrenOfContainerIds($containerIds);

						$timeperiods = $this->Timeperiod->timeperiodsByContainerId($containerIds, 'list');
						$contacts = $this->Contact->contactsByContainerId($containerIds, 'list');
						$contactGroups = $this->Contactgroup->contactgroupsByContainerId($containerIds, 'list');
					}
				}
				$serviceTemplate['Contact'] = $this->request->data['Servicetemplate']['Contact'];
				$serviceTemplate['Contactgroup'] = $this->request->data['Servicetemplate']['Contactgroup'];
			}
		}else{
			$serviceTemplate['Contact'] = Hash::combine($serviceTemplate['Contact'], '{n}.id', '{n}.id');
			$serviceTemplate['Contactgroup'] = Hash::combine($serviceTemplate['Contactgroup'], '{n}.id', '{n}.id');
		}

		$this->request->data = Hash::merge($serviceTemplate, $this->request->data);
		$this->set([
			'_timeperiods' => $timeperiods,
			'_contacts' => $contacts,
			'_contactgroups' => $contactGroups,
			'containers' => $containers,
			'servicetemplate' => $serviceTemplate,
			'commands' => $commands,
			'eventhandlers' => $eventHandlers,
			'commandarguments' => $commandarguments
		]);
	}


	public function add($servicetemplatetype_id = null){
		$_timeperiods = [];
		$_contacts = [];
		$_contactgroups = [];

		$userId = $this->Auth->user('id');
		// Checking if the user hit submit and a validation error happents, to refill input fields
		$Customvariable = [];
		$customFieldsToRefill = [
			'Servicetemplate' => [
				'notification_interval',
				'notify_on_recovery',
				'notify_on_warning',
				'notify_on_unknown',
				'notify_on_critical',
				'notify_on_flapping',
				'notify_on_downtime',
				'check_interval',
				'retry_interval',
				'process_performance_data',
				'active_checks_enabled',
				'flap_detection_enabled',
				'flap_detection_on_ok',
				'flap_detection_on_warning',
				'flap_detection_on_unknown',
				'flap_detection_on_critical',
				'priority',
				'Contact',
				'Contactgroup',
			],
		];
		$this->CustomValidationErrors->checkForRefill($customFieldsToRefill);

		//Fix that we dont lose any unsaved host macros, because of vaildation error
		if(isset($this->request->data['Customvariable'])){
			$Customvariable = $this->request->data['Customvariable'];
			$this->Frontend->setJson('customVariablesCount', sizeof($Customvariable));
		}else{
			$this->Frontend->setJson('customVariablesCount', 0);
		}

		$userContainerId = $this->Auth->user('container_id');

		$commands = $this->Command->serviceCommands('list');
		$eventhandlers = $this->Command->eventhandlerCommands('list');

		if($this->hasRootPrivileges === true){
			$containers = $this->Tree->easyPath($this->MY_RIGHTS, OBJECT_SERVICETEMPLATE, [], $this->hasRootPrivileges, [CT_SERVICETEMPLATEGROUP]);
		}else{
			$containers = $this->Tree->easyPath($this->getWriteContainers(), OBJECT_SERVICETEMPLATE, [], $this->hasRootPrivileges, [CT_SERVICETEMPLATEGROUP]);
		}

		$this->Frontend->set('data_placeholder', __('Please choose a contact'));
		$this->Frontend->set('data_placeholder_empty', __('No entries found'));
		$this->Frontend->setJson('lang_minutes', __('minutes'));
		$this->Frontend->setJson('lang_seconds', __('seconds'));
		$this->Frontend->setJson('lang_and', __('and'));

		$this->set('back_url', $this->referer());
		$this->set(compact(['containers', 'commands', 'eventhandlers', 'userContainerId', 'userValues', 'Customvariable']));
		if($this->request->is('post') || $this->request->is('put')){
			//Fixing structure of $this->request->data for HABTM

			if(isset($this->request->data['Servicetemplate']) && is_array($this->request->data['Servicetemplate'])){
				if(isset($this->request->data['Contact'])){
					$this->request->data['Servicetemplate']['Contact'] = $this->request->data['Contact'];
				}
				if(isset($this->request->data['Contactgroup'])){
					$this->request->data['Servicetemplate']['Contactgroup'] = $this->request->data['Contactgroup'];
				}
			}

			$ext_data_for_changelog =[
				'Contact' => [],
				'Contactgroup' => [],
				'EventhandlerCommand' => []
			];
			if($this->request->data('Servicetemplate.Contact')){

				if($contactsForChangelog = $this->Contact->find('list', [
					'conditions' => [
						'Contact.id' => $this->request->data['Servicetemplate']['Contact']
					]
				])){
					foreach($contactsForChangelog as $contactId => $contactName){
						$ext_data_for_changelog['Contact'][] = [
							'id' => $contactId,
							'name' => $contactName
						];
					}
					unset($contactsForChangelog);
				}
			}
			if($this->request->data('Servicetemplate.Contactgroup')){
				if($contactgroupsForChangelog = $this->Contactgroup->find('all', [
					'recursive' => -1,
					'contain' => [
						'Container' => [
							'fields' => [
								'Container.name'
							]
						]
					],
					'fields' => [
						'Contactgroup.id'
					],
					'conditions' => [
						'Contactgroup.id' => $this->request->data['Servicetemplate']['Contactgroup']
					]
				])){
					foreach($contactgroupsForChangelog as $contactgroupData){
						$ext_data_for_changelog['Contactgroup'][] = [
							'id' => $contactgroupData['Contactgroup']['id'],
							'name' => $contactgroupData['Container']['name']
						];
					}
					unset($contactgroupsForChangelog);
				}
			}
			if($this->request->data('Servicetemplate.notify_period_id')){
				if($timeperiodsForChangelog = $this->Timeperiod->find('list', [
					'conditions' => [
						'Timeperiod.id' => $this->request->data['Servicetemplate']['notify_period_id']
					]
				])){
					foreach($timeperiodsForChangelog as $timeperiodId => $timeperiodName){
						$ext_data_for_changelog['NotifyPeriod'] = [
							'id' => $timeperiodId,
							'name' => $timeperiodName
						];
					}
					unset($timeperiodsForChangelog);
				}
			}
			if($this->request->data('Servicetemplate.check_period_id')){
				if($timeperiodsForChangelog = $this->Timeperiod->find('list', [
					'conditions' => [
						'Timeperiod.id' => $this->request->data['Servicetemplate']['check_period_id']
					]
				])){
					foreach($timeperiodsForChangelog as $timeperiodId => $timeperiodName){
						$ext_data_for_changelog['CheckPeriod'] = [
							'id' => $timeperiodId,
							'name' => $timeperiodName
						];
					}
					unset($timeperiodsForChangelog);
				}
			}
			if($this->request->data('Servicetemplate.command_id')){
				if($commandsForChangelog = $this->Command->find('list', [
					'conditions' => [
						'Command.id' => $this->request->data['Servicetemplate']['command_id']
					]
				])){
					foreach($commandsForChangelog as $commandId => $commandName){
						$ext_data_for_changelog['CheckCommand'] = [
							'id' => $commandId,
							'name' => $commandName
						];
					}
					unset($commandsForChangelog);
				}
			}

			if($this->request->data('Servicetemplate.eventhandler_command_id')){
				if($commandsForChangelog = $this->Command->find('list', [
					'conditions' => [
						'Command.id' => $this->request->data['Servicetemplate']['eventhandler_command_id']
					]
				])){
					foreach($commandsForChangelog as $commandId => $commandName){
						$ext_data_for_changelog['EventhandlerCommand'] = [
							'id' => $commandId,
							'name' => $commandName
						];
					}
					unset($commandsForChangelog);
				}
			}

			$this->request->data['Servicetemplate']['uuid'] = $this->Servicetemplate->createUUID();

			if($servicetemplatetype_id !== null && is_numeric($servicetemplatetype_id)){
				$this->request->data['Servicetemplate']['servicetemplatetype_id'] = $servicetemplatetype_id;
			}

			$this->request->data['Contact'] = $this->request->data('Servicetemplate.Contact');
			$this->request->data['Contactgroup'] = $this->request->data('Servicetemplate.Contactgroup');

			$isJson = $this->request->ext == 'json';
			//Save everything including custom variables
			if($this->Servicetemplate->saveAll($this->request->data)){
				$changelogData = $this->Changelog->parseDataForChangelog(
					$this->params['action'],
					$this->params['controller'],
					$this->Servicetemplate->id,
					OBJECT_SERVICETEMPLATE,
					$this->request->data('Servicetemplate.container_id'),
					$userId,
					$this->request->data['Servicetemplate']['name'],
					array_merge($this->request->data, $ext_data_for_changelog)
				);
				if($changelogData){
					CakeLog::write('log', serialize($changelogData));
				}

				if($isJson){
					$this->serializeId();
				}else{
					$this->setFlash(__('<a href="/servicetemplates/edit/%s">Servicetemplate</a> successfully saved.', $this->Servicetemplate->id));
					$redirect = $this->Servicetemplate->redirect($this->request->params, ['action' => 'index']);
					$this->redirect($redirect);
				}
				//$this->redirect(array('action' => 'index'));
			}else{
				if($isJson){
					$this->serializeErrorMessage();
					return;
				}

				$this->setFlash(__('Could not save data'), false);
				$this->CustomValidationErrors->loadModel($this->Servicetemplate);
				$this->CustomValidationErrors->customFields(['notification_interval', 'check_interval', 'retry_interval', 'notify_on_recovery', 'flap_detection_on_up']);
				$this->CustomValidationErrors->fetchErrors();

				foreach($this->Customvariable->validationErrors as $customVariableValidationError){
					if(isset($customVariableValidationError['name'])){
						$this->set('customVariableValidationError', current($customVariableValidationError['name']));
					}
				}

				foreach($this->Customvariable->validationErrors as $customVariableValidationError){
					if(isset($customVariableValidationError['value'])){
						$this->set('customVariableValidationErrorValue', current($customVariableValidationError['value']));
					}
				}

				//Refil data that was loaded by ajax due to selected container id
				if($this->Container->exists($this->request->data('Servicetemplate.container_id'))){
					$container_id = $this->request->data('Servicetemplate.container_id');
					$containerIds = $this->Tree->resolveChildrenOfContainerIds($container_id);

					$_timeperiods = $this->Timeperiod->timeperiodsByContainerId($containerIds, 'list');
					$_contacts = $this->Contact->contactsByContainerId($containerIds, 'list');
					$_contactgroups = $this->Contactgroup->contactgroupsByContainerId($containerIds, 'list');
				}
			}
		}
		$this->set(compact(['_timeperiods', '_contacts', '_contactgroups']));
	}

	public function delete($id = null){
		$userId = $this->Auth->user('id');
		if(!$this->Servicetemplate->exists($id)){
			throw new NotFoundException(__('Invalid servicetemplate'));
		}

		if(!$this->request->is('post')){
			throw new MethodNotAllowedException();
		}
		$servicetemplate = $this->Servicetemplate->findById($id);

		if(!$this->allowedByContainerId(Hash::extract($servicetemplate, 'Container.id'))){
			$this->render403();
			return;
		}

		$this->Servicetemplate->id = $id;
		debug($this->Servicetemplate->__allowDelete($id));
		if($this->Servicetemplate->__allowDelete($id)){
			if($this->Servicetemplate->delete()){
				$changelog_data = $this->Changelog->parseDataForChangelog(
					$this->params['action'],
					$this->params['controller'],
					$id,
					OBJECT_SERVICETEMPLATE,
					$servicetemplate['Servicetemplate']['container_id'],
					$userId,
					$servicetemplate['Servicetemplate']['name'],
					$servicetemplate
				);
				if($changelog_data){
					CakeLog::write('log', serialize($changelog_data));
				}

				//Delete all services that were created using this template
				$this->loadModel('Service');
				$services = $this->Service->find('all', [
					'conditions' => [
						'Service.servicetemplate_id' => $id
					]
				]);
				foreach($services as $service){
					$this->Service->__delete($service, $this->Auth->user('id'));
				}
				$this->setFlash(__('Servicetemplate deleted.'));
				$this->redirect(array('action' => 'index'));
			}
			$this->setFlash(__('Could not delete servicetemplate'), false);
			$this->redirect(array('action' => 'index'));
		}
		$this->setFlash(__('Could not delete servicetemplate'), false);
		$this->redirect(array('action' => 'index'));
		
	}

	public function usedBy($id = null){
		if(!$this->Servicetemplate->exists($id)){
			throw new NotFoundException(__('Invalid servicetemplate'));
		}

		$servicetemplate = $this->Servicetemplate->findById($id);

		if(!$this->allowedByContainerId(Hash::extract($servicetemplate, 'Container.id'), false)){
			$this->render403();
			return;
		}

		$this->loadModel('Service');
		$_all_services = $this->Service->find('all', [
			'recursive' => -1,
			'joins' => [
				[
					'table' => 'servicetemplates',
					'alias' => 'Servicetemplate',
					'type' => 'INNER',
					'conditions' => [
						'Servicetemplate.id = Service.servicetemplate_id',
					]
				],
				[
					'table' => 'hosts',
					'alias' => 'Host',
					'type' => 'INNER',
					'conditions' => [
						'Host.id = Service.host_id',
					]
				],
				[
					'table' => 'hosts_to_containers',
					'alias' => 'HostsToContainers',
					'type' => 'LEFT',
					'conditions' => [
						'HostsToContainers.host_id = Host.id',

					]
				]
			],
			'conditions' => [
				'HostsToContainers.container_id' => $this->MY_RIGHTS,
				'Service.servicetemplate_id' => $id
			],
			'fields' => [
				'Host.id', 'Host.name', 'Host.address', 'Service.id', 'Service.host_id', 'Service.name', 'Servicetemplate.id', 'Servicetemplate.name', 'HostsToContainers.container_id'
			],
			'order'=> [
				'Host.name' => 'asc'
			],
		]);
		$all_hosts = [];
		$all_services = [];
		foreach($_all_services as $service){
			$all_hosts[$service['Host']['id']]['Host'] = $service['Host'];
			$all_hosts[$service['Host']['id']]['Container'][] = $service['HostsToContainers']['container_id'];

			if(!isset($all_services[$service['Host']['id']])){
				$all_services[$service['Host']['id']] = [];
			}

			$all_services[$service['Host']['id']][$service['Service']['id']] = [
				'Service' => $service['Service'],
				'Servicetemplate' => $service['Servicetemplate'],
			];
		}

		$this->set(compact(['all_services', 'all_hosts', 'servicetemplate']));
		$this->set('_serialize', ['all_services']);
		$this->set('back_url', $this->referer());
	}

	public function loadArguments($command_id = null, $servicetemplate_id = null){
		if(!$this->request->is('ajax')){
			throw new MethodNotAllowedException();
		}
		if(!$this->Servicetemplate->exists($servicetemplate_id)){
			throw new NotFoundException(__('Invalid servicetemplate'));
		}
		$commandarguments = [];
		$commandarguments = $this->Servicetemplatecommandargumentvalue->find('all', [
			'conditions' => [
				'Commandargument.command_id' => $command_id,
				'Servicetemplatecommandargumentvalue.servicetemplate_id' => $servicetemplate_id
			]
		]);
		//Checking if the servicetemplade has own arguments defined
		if(empty($commandarguments)){
			$commandarguments = $this->Commandargument->find('all', [
				'recursive' => -1,
				'conditions' => [
					'Commandargument.command_id' => $command_id,
				]
			]);
		}

		$this->set('commandarguments', $commandarguments);
	}

	public function loadContactsAndContactgroups($servicetemplate_id = null){
		$this->allowOnlyAjaxRequests();

		$this->loadModel('Contact');
		$this->loadModel('Contactgroup');

		$result = [
			'contacts' => [
				'contacts' => [],
				'sizeof' => 0
			],
			'contactgroups' => [
				'contactgroups' => [],
				'sizeof' => 0,
			]
		];
		//$result['contacts']['contacts'] = $this->Contact->contactsByServicetemplateId($servicetemplate_id, 'list');
		$containerIds = $this->Tree->resolveChildrenOfContainerIds($servicetemplate_id);
		$result['contacts']['contacts'] = $this->Contact->contactsByContainerId($containerIds, 'list');

		$result['contacts']['sizeof'] = sizeof($result['contacts']['contacts']);
		//container_id = 1 => ROOT
		$result['contactgroups']['contactgroups'] = $this->Contactgroup->contactgroupsByContainerId($containerIds, 'list');
		$result['contactgroups']['sizeof'] = sizeof($result['contactgroups']['contactgroups']);

		$this->set(compact(['result']));
		$this->set('_serialize', ['result']);

	}

	public function loadArgumentsAdd($command_id = null){
		$this->allowOnlyAjaxRequests();
		$this->loadModel('Commandargument');

		//Deleting associations that we dont get values of other hosttemplates
		$this->Commandargument->unbindModel(
			['hasOne' => ['Servicetemplatecommandargumentvalue', 'Servicecommandargumentvalue', 'Hosttemplatecommandargumentvalue', 'Hostcommandargumentvalue']]
		);
		$commandarguments = $this->Commandargument->find('all', [
			'conditions' => [
				'Commandargument.command_id' => $command_id,
			]
		]);

		$this->set('commandarguments', $commandarguments);
		$this->render('load_arguments');
	}

	public function loadNagArgumentsAdd($command_id = null){
		$this->allowOnlyAjaxRequests();
		$this->loadModel('Commandargument');

		//Deleting associations that we dont get values of other hosttemplates
		$this->Commandargument->unbindModel(
			['hasOne' => ['Servicetemplatecommandargumentvalue', 'Servicecommandargumentvalue', 'Hosttemplatecommandargumentvalue', 'Hostcommandargumentvalue']]
		);
		$commandarguments = $this->Commandargument->find('all', [
			'conditions' => [
				'Commandargument.command_id' => $command_id,
			]
		]);

		$this->set('commandarguments', $commandarguments);
		$this->render('load_nag_arguments');
	}

	public function addCustomMacro($counter){
		$this->allowOnlyAjaxRequests();

		$this->set('objecttype_id', OBJECT_SERVICETEMPLATE);
		$this->set('counter', $counter);
	}

	/**
	 * Loads the paramters for 'Check Command'.
	 */
	public function loadParametersByCommandId($command_id = null, $servicetemplate_id = null){
		$this->allowOnlyAjaxRequests();

		$test = [];
		$commandarguments = [];
		if($command_id){
			$commandarguments = $this->Commandargument->find('all', [
				'recursive' => -1,
				'conditions' => [
					'Commandargument.command_id' => $command_id
				]
			]);
			foreach($commandarguments as $key => $commandargument){
				if($servicetemplate_id){
					$servicetemplate_command_argument_value = $this->Servicetemplatecommandargumentvalue->find('first', [
						'conditions' => [
							'Servicetemplatecommandargumentvalue.servicetemplate_id' => $servicetemplate_id,
							'Servicetemplatecommandargumentvalue.commandargument_id' => $commandargument['Commandargument']['id'],
						],
						'fields' => [
							'Servicetemplatecommandargumentvalue.value',
							'Servicetemplatecommandargumentvalue.id',
						],
					]);
					if(isset($servicetemplate_command_argument_value['Servicetemplatecommandargumentvalue']['value'])){
						$commandarguments[$key]['Servicetemplatecommandargumentvalue']['value'] =
							$servicetemplate_command_argument_value['Servicetemplatecommandargumentvalue']['value'];
					}
					if(isset($servicetemplate_command_argument_value['Servicetemplatecommandargumentvalue']['id'])){
						$commandarguments[$key]['Servicetemplatecommandargumentvalue']['id'] =
							$servicetemplate_command_argument_value['Servicetemplatecommandargumentvalue']['id'];
					}
				}
			}
		}

		$this->set(compact('commandarguments'));
	}

	/**
	 * Loads the parameters for the 'Eventhandler check command'.
	 */
	public function loadNagParametersByCommandId($command_id = null, $servicetemplate_id = null){
		$this->allowOnlyAjaxRequests();

		$test = [];
		$commandarguments = [];
		if($command_id){
			$commandarguments = $this->Commandargument->find('all', [
				'recursive' => -1,
				'conditions' => [
					'Commandargument.command_id' => $command_id
				]
			]);
			foreach($commandarguments as $key => $commandargument){
				if($servicetemplate_id){
					$servicetemplate_command_argument_value = $this->Servicetemplateeventcommandargumentvalue->find('first', [
						'conditions' => [
							'Servicetemplateeventcommandargumentvalue.servicetemplate_id' => $servicetemplate_id,
							'Servicetemplateeventcommandargumentvalue.commandargument_id' => $commandargument['Commandargument']['id'],
						],
						'fields' => [
							'Servicetemplateeventcommandargumentvalue.value',
							'Servicetemplateeventcommandargumentvalue.id',
						],
					]);
					if(isset($servicetemplate_command_argument_value['Servicetemplateeventcommandargumentvalue']['value'])){
						$commandarguments[$key]['Servicetemplateeventcommandargumentvalue']['value'] =
							$servicetemplate_command_argument_value['Servicetemplateeventcommandargumentvalue']['value'];
					}
					if(isset($servicetemplate_command_argument_value['Servicetemplateeventcommandargumentvalue']['id'])){
						$commandarguments[$key]['Servicetemplateeventcommandargumentvalue']['id'] =
							$servicetemplate_command_argument_value['Servicetemplateeventcommandargumentvalue']['id'];
					}
				}
			}
		}

		$this->set(compact('commandarguments'));
	}

	public function loadElementsByContainerId($containerId = null){
		$this->allowOnlyAjaxRequests();
		if(!$this->Container->exists($containerId)){
			throw new NotFoundException(__('Invalid hosttemplate'));
		}

		$containerIds = $this->Tree->resolveChildrenOfContainerIds($containerId);

		$timeperiods = $timeperiods = $this->Timeperiod->timeperiodsByContainerId($containerIds, 'list');
		$timeperiods = $this->Timeperiod->makeItJavaScriptAble($timeperiods);
		$checkperiods = $timeperiods;

		$contacts = $this->Contact->contactsByContainerId($containerIds, 'list');
		$contacts = $this->Timeperiod->makeItJavaScriptAble($contacts);

		$contactgroups = $this->Contactgroup->contactgroupsByContainerId($containerIds, 'list');
		$contactgroups = $this->Timeperiod->makeItJavaScriptAble($contactgroups);

		$this->set(compact(['timeperiods', 'checkperiods', 'contacts', 'contactgroups']));
		$this->set('_serialize', ['timeperiods', 'checkperiods', 'contacts', 'contactgroups']);
	}
}

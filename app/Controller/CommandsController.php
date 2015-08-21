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


class CommandsController extends AppController{
	public $uses = ['Command', 'Commandargument', 'Macro'];
	public $layout = 'Admin.default';
	public $components = array('Paginator', 'ListFilter.ListFilter', 'RequestHandler');
	public $helpers = array('ListFilter.ListFilter');

	/**
	 * Define the search function for each field that should be searchable
	 */
	public $listFilters = [
		'index' => [
			'fields' => [
				'Command.name' => ['label' => 'Commandname', 'searchType' => 'wildcard'],
			]
		],
		'hostchecks' => [
			'fields' => [
				'Command.name' => ['label' => 'Commandname', 'searchType' => 'wildcard'],
			]
		],
		'notifications' => [
			'fields' => [
				'Command.name' => ['label' => 'Commandname', 'searchType' => 'wildcard'],
			]
		],
		'handler' => [
			'fields' => [
				'Command.name' => ['label' => 'Commandname', 'searchType' => 'wildcard'],
			]
		]
	];

	public function index(){
		$query = [
			'limit' => 150,
			'recursive' => -1,
			'order' => [
				'Command.name' => 'asc'
			],
			'conditions' => [
				'Command.command_type' => CHECK_COMMAND
			],
		];
		
		//Add all commands to result for API requests
		if($this->isJsonRequest() || $this->isXmlRequest()){
			unset($query['conditions']['Command.command_type']);
			$query['limit'] = 999999;
		}
		$this->Paginator->settings = Hash::merge($this->Paginator->settings, $query);
		$all_commands = $this->Paginator->paginate();
		$this->set('isFilter', false);
		if(isset($this->request->data['Filter']) && $this->request->data['Filter'] !== null){
			$this->set('isFilter', true);
		}
		$this->set('_serialize', ['all_commands']);
		$this->set(compact(['all_commands']));
	}
	
	public function hostchecks(){
		$query = [
			'limit' => 150,
			'recursive' => -1,
			'order' => [
				'Command.name' => 'asc'
			],
			'conditions' => [
				'Command.command_type' => HOSTCHECK_COMMAND
			],
		];
		
		$this->Paginator->settings = Hash::merge($this->Paginator->settings, $query);
		$all_commands = $this->Paginator->paginate();
		$this->set('isFilter', false);
		if(isset($this->request->data['Filter']) && $this->request->data['Filter'] !== null){
			$this->set('isFilter', true);
		}
		$this->set('_serialize', ['all_commands']);
		$this->set(compact(['all_commands']));
	}
	
	public function notifications(){
		$query = [
			'limit' => 150,
			'recursive' => -1,
			'order' => [
				'Command.name' => 'asc'
			],
			'conditions' => [
				'Command.command_type' => NOTIFICATION_COMMAND
			],
		];
		
		$this->Paginator->settings = Hash::merge($this->Paginator->settings, $query);
		$all_commands = $this->Paginator->paginate();
		$this->set('isFilter', false);
		if(isset($this->request->data['Filter']) && $this->request->data['Filter'] !== null){
			$this->set('isFilter', true);
		}
		$this->set('_serialize', ['all_commands']);
		$this->set(compact(['all_commands']));
	}
	
	public function handler(){
		$query = [
			'limit' => 150,
			'recursive' => -1,
			'order' => [
				'Command.name' => 'asc'
			],
			'conditions' => [
				'Command.command_type' => EVENTHANDLER_COMMAND
			],
		];
		
		$this->Paginator->settings = Hash::merge($this->Paginator->settings, $query);
		$all_commands = $this->Paginator->paginate();
		$this->set('isFilter', false);
		if(isset($this->request->data['Filter']) && $this->request->data['Filter'] !== null){
			$this->set('isFilter', true);
		}
		$this->set('_serialize', ['all_commands']);
		$this->set(compact(['all_commands']));
	}

	public function add(){
		$userId = $this->Auth->user('id');
		$this->Frontend->setJson('console_welcome', $this->Command->getConsoleWelcome($this->systemname));
		$this->Frontend->setJson('websocket_url', 'wss://' . env('HTTP_HOST') . '/sudo_server');
		$this->loadModel('Systemsetting');
		$key = $this->Systemsetting->findByKey('SUDO_SERVER.API_KEY');
		$this->Frontend->setJson('akey', $key['Systemsetting']['value']);
		$this->set('command_types', $this->getCommandTypes());

		if($this->request->is('post') || $this->request->is('put')){
			$this->request->data['Command']['uuid'] = $this->Command->createUUID();
			$this->request->data = $this->rewritePostData();

			if($this->Command->saveAll($this->request->data)){
				$changeLogData = $this->Changelog->parseDataForChangelog(
					$this->params['action'],
					$this->params['controller'],
					$this->Command->id,
					OBJECT_COMMAND,
					[ROOT_CONTAINER],
					$userId,
					$this->request->data['Command']['name'],
					$this->request->data
				);
				if($changeLogData){
					CakeLog::write('log', serialize($changeLogData));
				}

				if($this->request->ext == 'json'){
					$this->serializeId(); // REST API ID serialization
					return;
				}

				// Redirect normal browser POST requests only, not for REST API requests
				$this->setFlash(__('Command successfully saved'));
				$redirect = $this->Command->redirect($this->request->params, ['action' => 'index']);
				$this->redirect($redirect);
			}else{
				if($this->request->ext == 'json'){
					$this->serializeErrorMessage();
					return;
				}

				$this->setFlash(__('Could not save data'), false);
			}
		}
	}

	public function edit($id = null){
		$userId = $this->Auth->user('id');
		//Checking if the id/ids are ture ids
		if($this->Command->exists(['Command.id' => $id])){
			$command = $this->Command->findById($id);
			$command['Commandargument'] = Hash::sort($command['Commandargument'], '{n}.name', 'asc', 'natural');

			$command_types = $this->getCommandTypes();
			$this->set(compact(['command', 'command_types']));
			$this->set('_serialize', ['command', 'command_types']);
			$this->Frontend->setJson('console_welcome', $this->Command->getConsoleWelcome($this->systemname));
			//$this->Frontend->setJson('websocket_host', env('HTTP_HOST'));
			//$this->Frontend->setJson('websocket_port', 8081);
			$this->Frontend->setJson('websocket_url', 'wss://' . env('HTTP_HOST') . '/sudo_server');
			$this->loadModel('Systemsetting');
			$key = $this->Systemsetting->findByKey('SUDO_SERVER.API_KEY');
			$this->Frontend->setJson('akey', $key['Systemsetting']['value']);
			$this->Frontend->setJson('command_id', $id);

			if($this->request->is('post') || $this->request->is('put')){
				$this->request->data = $this->rewritePostData();

				//Checking if the user delete a argument
				if(!empty($command['Commandargument']) && !empty($this->request->data['Commandargument'])){
					$argumentsToDelete = array_diff(Hash::extract($command['Commandargument'], '{n}.id'), Hash::extract($this->request->data['Commandargument'], '{n}.id'));
					//Delete all arguments that was removed by the user:
					foreach($argumentsToDelete as $argumentToDelete){
						$this->Commandargument->delete($argumentToDelete);
					}
				}

				if($this->Command->saveAll($this->request->data)){
					$changelog_data = $this->Changelog->parseDataForChangelog(
						$this->params['action'],
						$this->params['controller'],
						$this->Command->id,
						OBJECT_COMMAND,
						[ROOT_CONTAINER],
						$userId,
						$this->request->data['Command']['name'],
						$this->request->data,
						$command
					);
					if($changelog_data){
						CakeLog::write('log', serialize($changelog_data));
					}

					$this->setFlash(__('Command successfully saved'));
					$redirect = $this->Command->redirect($this->request->params, ['action' => 'index']);
					$this->redirect($redirect);
				}else{
					$this->setFlash(__('Could not save data'), false);
				}
			}
		}else{
			throw new NotFoundException(__('Command not found'));
		}
	}

	public function delete($id = null){
		$userId = $this->Auth->user('id');
		if(!$this->request->is('post')){
			throw new MethodNotAllowedException();
		}

		$this->Command->id = $id;
		if(!$this->Command->exists()){
			throw new NotFoundException(__('Invalid command'));
		}

		$command = $this->Command->findById($id);

		if($this->__allowDelete($command)){
			if($this->Command->delete()){
				$changelog_data = $this->Changelog->parseDataForChangelog(
					$this->params['action'],
					$this->params['controller'],
					$id,
					OBJECT_COMMAND,
					[ROOT_CONTAINER],
					$userId,
					$command['Command']['name'],
					$command
				);
				if($changelog_data){
					CakeLog::write('log', serialize($changelog_data));
				}
				$this->setFlash(__('Command deleted'));
				$this->redirect(['action' => 'index']);
			}
		}else{
			$count = 1;
			$commandsCanotDelete = [$command['Command']['name']];
			$commandsToDelete = [];
			$this->set(compact(['commandsToDelete', 'commandsCanotDelete', 'count']));
			$this->render('mass_delete');
			return;
		}
		$this->setFlash(__('Could not delete command'), false);
		$this->redirect(['action' => 'index']);

	}

	public function mass_delete($id = null){
		if($this->request->is('post') || $this->request->is('put')){
			//Delete the commands and forward to index
			foreach($this->request->data('Command.delete') as $command_id){
				$command = $this->Command->findById($command_id);
				if($this->__allowDelete($command)){
					$this->__delete($command);
				}
			}
			$this->setFlash('Commands deleted');
			$this->redirect(array('action' => 'index'));
		}

		$commandsToDelete = [];
		$commandsCanotDelete = [];
		$count = 0;

		foreach(func_get_args() as $command_id){
			if($this->Command->exists($command_id)){
				$command = $this->Command->findById($command_id);
				if($this->__allowDelete($command)){
					$commandsToDelete[] = $command;
				}else{
					$commandsCanotDelete[] = $command['Command']['name'];
				}
			}
		}

		$count = sizeof($commandsToDelete) + sizeof($commandsCanotDelete);
		$this->set(compact(['commandsToDelete', 'commandsCanotDelete', 'count']));
		$this->set('back_url', $this->referer());
	}

	protected function __delete($command){
		$this->Command->id = $command['Command']['id'];
		if($this->Command->delete()){
			$changelog_data = $this->Changelog->parseDataForChangelog(
				'delete',
				$this->params['controller'],
				$command['Command']['id'],
				OBJECT_COMMAND,
				[ROOT_CONTAINER],
				$userId,
				$command['Command']['name'],
				$command
			);
			if($changelog_data){
				CakeLog::write('log', serialize($changelog_data));
			}
			return true;
		}
		return false;
	}

	protected function __allowDelete($command){
		//Check if the command is used somewere, if yes we can not delete it!
		$this->loadModel('__ContactsToServicecommands');
		$contactCount = $this->__ContactsToServicecommands->find('count', [
			'conditions' => [
				'__ContactsToServicecommands.command_id' => $command['Command']['id']
			]
		]);
		if($contactCount > 0){
			return false;
		}

		$this->loadModel('__ContactsToHostcommands');
		$contactCount = $this->__ContactsToHostcommands->find('count', [
			'conditions' => [
				'__ContactsToHostcommands.command_id' => $command['Command']['id']
			]
		]);
		if($contactCount > 0){
			return false;
		}

		$this->loadModel('Hosttemplate');
		$hostCount = $this->Hosttemplate->find('count', [
			'conditions' => [
				'Hosttemplate.command_id' => $command['Command']['id']
			]
		]);
		if($hostCount > 0){
			return false;
		}

		$this->loadModel('Servicetemplate');
		$serviceCount = $this->Servicetemplate->find('count', [
			'conditions' => [
				'Servicetemplate.command_id' => $command['Command']['id']
			]
		]);
		if($serviceCount > 0){
			return false;
		}

		$this->loadModel('Host');
		$hostCount = $this->Host->find('count', [
			'conditions' => [
				'Host.command_id' => $command['Command']['id']
			]
		]);
		if($hostCount > 0){
			return false;
		}

		$this->loadModel('Service');
		$serviceCount = $this->Service->find('count', [
			'conditions' => [
				'Service.command_id' => $command['Command']['id']
			]
		]);
		if($serviceCount > 0){
			return false;
		}

		return true;
	}

	public function addCommandArg($id = null){
		$this->allowOnlyAjaxRequests();

		//Fetching arguments out of $_POST or the database
		if(!empty($this->request->data)){
			$all_arguments = $this->request->data;
		}elseif($id !== null){
			$all_arguments = $this->Commandargument->find('list', [
				'conditions' => [
					'command_id' => $this->Command->findById($id)['Command']['id'],
				],
			]);
		}else{
			$all_arguments = [];
		}

		$argumentsCount = 1;

		while(in_array('$ARG' . $argumentsCount . '$', $all_arguments)){
			$argumentsCount++;
		}

		$newArgument = '$ARG' . $argumentsCount . '$';
		$this->set(compact(['newArgument', 'argumentsCount', 'id']));
	}

	public function loadMacros(){
		$all_macros = $this->Macro->find('all');

		//Sorting the SQL result in a human frindly way. Will sort $USER10$ below $USER2$
		$all_macros = Hash::sort($all_macros, '{n}.Macro.name', 'asc', 'natural');

		$this->set('all_macros', $all_macros);
	}

	private function getCommandTypes(){
		return [
			CHECK_COMMAND => __('Servicecheck command'),
			HOSTCHECK_COMMAND => __('Hostcheck command'),
			NOTIFICATION_COMMAND => __('Notification command'),
			EVENTHANDLER_COMMAND => __('Eventhandler command'),
		];
	}

	private function rewritePostData(){
		$requestData = $this->request->data;
		// See MacrosController.php function _rewritePostData() for more information about this
		$Commandarguments = [];
		if(isset($this->request->data['Commandargument'])){
			$Commandarguments = $this->request->data['Commandargument'];
			$requestData['Commandargument'] = [];
		}
		foreach($Commandarguments as $data){
			// Remove empty values, because nagios will throw a config error
			if(!isset($data['name']) || strlen($data['name']) == 0 || !isset($data['human_name']) || strlen($data['human_name']) == 0){
				continue;
			}
			$requestData['Commandargument'][] = $data;
		}

		return $requestData;
	}

	/**
	 * This function creates for each command a new UUID. Normally you should never execute this function!
	 * ! Caution: May be destroy your whole system!
	 * ! Only execute this if you know what you are doing!
	 * @author Daniel Ziegler <daniel.ziegler@it-novum.com>
	 * @since 3.0
	 *
	 */
	protected function resetAllUUID(){
		throw new BadRequestException('To call this function is a really bad idea, because all your UUIDs get lost and generated new. So this function is disabled by default!');
		return false;
		foreach($this->Command->find('all', array('fields' => array('uuid', 'id'))) as $command){
			debug($command);
			$command['Command']['uuid'] = $this->Command->createUUID();
			$this->Command->save($command);
		}
	}

	private function getConsoleWelcome(){
		return "This is a terminal connected to your " . $this->systemname . " " .
			"Server, this is very powerful to test and debug plugins.\n" .
			"User: \033[31mnagios\033[0m\nPWD: \033[35m/opt/openitc/nagios/libexec/\033[0m\n\n";
	}
	
	//ALC permission
	public function terminal(){
		return null;
	}
}

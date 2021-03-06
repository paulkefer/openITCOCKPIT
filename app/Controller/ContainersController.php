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

use itnovum\openITCOCKPIT\Core\ModuleManager;


/**
 * Class ContainersController
 * @property Container $Container
 */
class ContainersController extends AppController {
    public $layout = 'Admin.default';
    public $helpers = ['Nest'];

    public function index() {
        $this->layout = 'angularjs';
        if ($this->request->is('post') || $this->request->is('put')) {
            $this->request->data['Container']['containertype_id'] = CT_NODE;
            $this->Container->create();
            if ($this->Container->save(Hash::remove($this->request->data, 'Container.id'))) {
                Cache::clear(false, 'permissions');
                $this->setFlash(__('new node created successfully'));
            } else {
                $this->setFlash(__('error while saving data'), false);
            }
        }
        $all_containers = $this->Container->find('all', [
            'recursive' => -1,
        ]);
        $tenants = Hash::combine(Hash::extract($all_containers, '{n}.Container[containertype_id=' . CT_TENANT . ']'), '{n}.id', '{n}.name');

        $this->set('validationError', (!empty($this->Container->validationErrors) ? true : false));

        $this->set(compact(['all_containers', 'tenants']));
        $this->set('_serialize', ['all_containers']);
    }

    public function nest() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $all_container = $this->Container->find('all', [
            'recursive' => -1,
        ]);
        $all_container = Hash::nest($all_container);
        $this->set('all_container', $all_container);
        $this->set('_serialize', ['all_container']);
    }

    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();

        }
        if (!$this->Container->exists($id)) {
            throw new NotFoundException(__('Invalid container'));
        }
        $container = $this->Container->findById($id);
        if (!$this->allowedByContainerId($container['Container']['id'])) {
            throw new ForbiddenException('403 Forbidden');
        }

        $this->set('container', $container);
        $this->set('_serialize', ['container']);
    }

    protected function tree($id = 0) {
        debug($this->Container->generateTreeList());
    }

    public function add() {
        $this->layout = 'blank';
        if (!$this->request->is('post') && !$this->request->is('put') && $this->request->ext == 'json') {
            return;
        }
        if ($this->request->ext == 'json') {
            if ($this->Container->saveAll($this->request->data)) {
                Cache::clear(false, 'permissions');
                $this->serializeId();

                return;
            }
            $this->serializeErrorMessage();
        }
    }

    public function edit() {
        $this->layout = 'blank';
        if (!$this->isAngularJsRequest()) {
            return;
        }
        if ($this->request->is('post')) {
            $containerId = $this->request->data['Container']['id'];
            $containerTypeId = $this->request->data['Container']['containertype_id'];
            if (!$this->Container->exists($containerId) || $containerTypeId != 5) {
                throw new NotFoundException(__('Invalid container'));
            }

            if (!$this->Container->save($this->request->data)) {
                Cache::clear(false, 'permissions');
                $this->serializeErrorMessage();
            } else {
                $this->serializeId();
            }
        }
    }

    /**
     * recovers the container tree if left and/or right is missing or broken
     * Wrapper public function of CakePHP´s TreeBehavior::recover
     *
     * @param string $mode
     * @param        array $$missingParentAction
     *
     * @link  http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::recover
     * @since 3.0
     */
    protected function recover($mode = 'parent', $missingParentAction = null) {
        $this->Container->recover($mode, $missingParentAction);
    }

    /**
     * Is called by AJAX to rander the nest list in Nodes
     *
     * @param int $id the id of the tenant
     *
     * @author Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since  3.0
     */
    public function byTenant($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        if (!$this->Container->hasAny()) {
            throw new NotFoundException(__('tenant.notfound'));
        }
        $parent = $this->Container->find('all', [
            'recursive'  => -1,
            'conditions' => [
                'id' => $id,
            ],
        ]);

        $parent[0]['Container']['allow_edit'] = false;
        if (isset($this->MY_RIGHTS_LEVEL[$parent[0]['Container']['id']])) {
            if ((int)$this->MY_RIGHTS_LEVEL[$parent[0]['Container']['id']] === WRITE_RIGHT) {
                $parent[0]['Container']['allow_edit'] = true;
            }
        }
        $containers = $this->Container->children($id, false, null, 'name');
        foreach ($containers as $key => $container) {
            $containers[$key]['Container']['allow_edit'] = false;
            $containerId = $container['Container']['id'];
            if (isset($this->MY_RIGHTS_LEVEL[$containerId])) {
                if ((int)$this->MY_RIGHTS_LEVEL[$containerId] === WRITE_RIGHT) {
                    $containers[$key]['Container']['allow_edit'] = true;
                }
            }
        }
        $hasChilds = true;
        if (empty($containers) && !empty($parent[0])) {
            $containers = $parent[0];
            $hasChilds = false;
        }
        $nest = Hash::nest($containers);
        $parent[0]['children'] = ($hasChilds) ? $nest : [];
        $this->set('nest', $parent);
        $this->set('_serialize', ['nest']);
    }

    /**
     * Randers the selectbox with all the nodes and path of the tenant
     * ### Options
     * Please check at Tree->easyPath()
     *
     * @param int $id of the tenant
     * @param array $options Array of options and HTML attributes.
     *
     * @author Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since  3.0
     */
    public function byTenantForSelect($id = null, $options = []) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $this->set('paths', $this->Tree->easyPath($this->Tree->resolveChildrenOfContainerIds($id), OBJECT_NODE));
        $this->set('_serialize', ['paths']);
    }

    public function delete($id = null) {
        $userId = $this->Auth->user('id');
        if (!$this->Container->exists($id)) {
            throw new NotFoundException(__('Invalid container'));
        }
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }
        $modulePlugins = array_filter(CakePlugin::loaded(), function ($value) {
            return strpos($value, 'Module') !== false;
        });

        $rootContainer = $this->Container->find('first', [
            'recursive'  => -1,
            'conditions' => [
                'Container.id' => $id,
            ],
        ]);
        $childElements = $this->Container->find('all', [
            'recursive'  => -1,
            'conditions' => [
                'AND' => [
                    'Container.lft BETWEEN ? AND ?'  => [$rootContainer['Container']['lft'], $rootContainer['Container']['rght']],
                    'Container.rght BETWEEN ? AND ?' => [$rootContainer['Container']['lft'], $rootContainer['Container']['rght']],
                    'Container.containertype_id'     => [
                        CT_LOCATION,
                        CT_NODE,
                        CT_HOSTGROUP,
                        CT_SERVICEGROUP,
                        CT_CONTACTGROUP,
                    ],
                ],
            ],
        ]);
        $allowDeleteRoot = true;
        $childContainers = Hash::combine($childElements, '{n}.Container.id', '{n}.Container.name', '{n}.Container.containertype_id');
        if (is_array($childContainers) && !empty($childContainers)) {
            foreach ($childContainers as $containerTypeId => $containers) {
                $containerIds = array_keys($containers);
                switch ($containerTypeId) {
                    case CT_NODE:
                        //Check hosts to delete
                        $Host = ClassRegistry::init('Host');
                        $hostsToDelete = $Host->find('all', [
                            'recursive'  => -1,
                            'conditions' => [
                                'Host.container_id' => $containerIds
                            ]
                        ]);
                        $hostIds = Hash::extract($hostsToDelete, '{n}.Host.id');
                        $allowDelete = $this->Container->__allowDelete($hostIds);
                        $allowDeleteRoot = $allowDelete;
                        $usersToDelete = [];

                        //Check users to delete
                        $User = ClassRegistry::init('User');
                        $usersByContainerId = $this->User->usersByContainerId($containerIds, 'list');
                        if (!empty($usersByContainerId)) {
                            $usersToDelete = $this->User->find('all', [
                                'recursive'  => -1,
                                'conditions' => [
                                    'User.id' => array_keys($usersByContainerId)
                                ],
                                'contain'    => [
                                    'ContainerUserMembership' => [
                                        'conditions' => [
                                            'NOT' => [
                                                'ContainerUserMembership.container_id' => $containerIds
                                            ]
                                        ]
                                    ]
                                ],
                                'fields'     => [
                                    'User.id'
                                ]
                            ]);
                        }

                        $usersToDelete = Hash::combine($usersToDelete, '{n}.User.id', '{n}.ContainerUserMembership');
                        if ($allowDelete) {
                            foreach ($usersByContainerId as $user => $username) {
                                if (empty($usersToDelete[$user])) {
                                    $User->__delete($user, $userId);
                                }
                            }
                        }

                        //Check satellites to delete
                        if (in_array('DistributeModule', $modulePlugins)) {
                            $Satellite = ClassRegistry::init('DistributeModule.Satellite');
                            $satellitesToDelete = $Satellite->find('all', [
                                'recursive'  => -1,
                                'joins'      => [
                                    [
                                        'table'      => 'containers',
                                        'alias'      => 'Container',
                                        'type'       => 'INNER',
                                        'conditions' => [
                                            'Container.id = Satellite.container_id',
                                        ],
                                    ],
                                ],
                                'conditions' => [
                                    'Satellite.container_id' => $containerIds,
                                ],

                                'fields' => [
                                    'Satellite.id',
                                    'Container.id',
                                ],
                            ]);
                            if ($allowDelete) {
                                foreach ($satellitesToDelete as $satellite) {
                                    $Satellite->__delete($satellite, $userId);
                                }
                            }
                        }

                        if ($allowDelete) {
                            foreach ($hostsToDelete as $host) {
                                $Host->__delete($host, $userId);
                            }
                        }
                        break;
                    case CT_LOCATION:
                        //Check locations to delete
                        $Location = ClassRegistry::init('Location');
                        $locationsToDelete = $Location->find('all', [
                            'recursive'  => -1,
                            'joins'      => [
                                [
                                    'table'      => 'containers',
                                    'alias'      => 'Container',
                                    'type'       => 'INNER',
                                    'conditions' => [
                                        'Container.id = Location.container_id',
                                    ],
                                ],
                            ],
                            'conditions' => [
                                'Location.container_id' => $containerIds,
                            ],
                            'fields'     => [
                                'Location.id',
                                'Container.id',
                            ],
                        ]);
                        foreach ($locationsToDelete as $location) {
                            $Location->__delete($location, $userId);
                        }
                        break;
                    case CT_HOSTGROUP:
                        //Check host groups to delete
                        $Hostgroup = ClassRegistry::init('Hostgroup');
                        $hostgroupsToDelete = $Hostgroup->find('all', [
                            'recursive'  => -1,
                            'contain'    => [
                                'Container' => [
                                    'fields' => [
                                        'Container.id',
                                    ],
                                ],
                            ],
                            'conditions' => [
                                'Hostgroup.container_id' => $containerIds,
                            ],
                            'fields'     => [
                                'Hostgroup.id',
                            ],
                        ]);
                        foreach ($hostgroupsToDelete as $containerId) {
                            $this->Container->__delete($containerId);
                        }
                        break;
                    case CT_SERVICEGROUP:
                        //Check service groups to delete
                        $Servicegroup = ClassRegistry::init('Servicegroup');
                        $servicegroupsToDelete = $Servicegroup->find('all', [
                            'recursive'  => -1,
                            'contain'    => [
                                'Container' => [
                                    'fields' => [
                                        'Container.id',
                                    ],
                                ],
                            ],
                            'conditions' => [
                                'Servicegroup.container_id' => $containerIds,
                            ],
                            'fields'     => [
                                'Servicegroup.id',
                            ],
                        ]);
                        foreach ($servicegroupsToDelete as $containerId) {
                            $this->Container->__delete($containerId);
                        }
                        break;
                    case CT_CONTACTGROUP:
                        //Check contact groups to delete
                        $Contactgroup = ClassRegistry::init('Contactgroup');
                        $contactgroupsToDelete = $Contactgroup->find('all', [
                            'recursive'  => -1,
                            'contain'    => [
                                'Container' => [
                                    'fields' => [
                                        'Container.id',
                                    ],
                                ],
                            ],
                            'conditions' => [
                                'Contactgroup.container_id' => $containerIds,
                            ],
                            'fields'     => [
                                'Contactgroup.id',
                            ],
                        ]);
                        foreach ($contactgroupsToDelete as $containerId) {
                            $this->Container->__delete($containerId);
                        }
                        break;
                }
            }
        }
        Cache::clear(false, 'permissions');
        if ($allowDeleteRoot) {
            if ($this->Container->__delete($id)) {
                Cache::clear(false, 'permissions');
                $this->setFlash(__('Container deleted'));
                $this->redirect(['action' => 'index']);
            } else {
                $this->setFlash(__('Could not delete container'), false);
                $this->redirect(['action' => 'index']);
            }
        }
        $this->setFlash(__('Could not delete container'), false);
        $this->redirect(['action' => 'index']);

    }

    public function loadContainersForAngular() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        if ($this->hasRootPrivileges === true) {
            $containers = $this->Container->makeItJavaScriptAble(
                $this->Tree->easyPath($this->MY_RIGHTS, OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP])
            );
        } else {
            $containers = $this->Container->makeItJavaScriptAble(
                $containers = $this->Tree->easyPath($this->getWriteContainers(), OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP])
            );
        }

        $this->set(compact(['containers']));
        $this->set('_serialize', ['containers']);
    }

    public function showDetails($id = null) {
        $this->layout = 'angularjs';

        if (!$this->isAngularJsRequest()) {
            $this->set('back_url', $this->referer());
        }
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }
        if (!$this->allowedByContainerId($id)) {
            $this->render403();
            return;
        }
        if (!$this->Container->exists($id)) {
            throw new NotFoundException(__('Invalid container'));
        }

        $this->Container->bindModel([
                'hasMany' => [
                    'Hostdependency',
                    'Servicedependency',
                    'Hostescalation',
                    'Serviceescalation',
                    'ContainerNode'                 => [
                        'className'  => 'Container',
                        'foreignKey' => 'parent_id',
                        'conditions' => [
                            'ContainerNode.containertype_id' => CT_NODE
                        ]
                    ],
                    'ContainerLocation'             => [
                        'className'  => 'Container',
                        'foreignKey' => 'parent_id',
                        'conditions' => [
                            'ContainerLocation.containertype_id' => CT_LOCATION
                        ]
                    ],
                    'ContainerHostgroup'            => [
                        'className'  => 'Container',
                        'foreignKey' => 'parent_id',
                        'conditions' => [
                            'ContainerHostgroup.containertype_id' => CT_HOSTGROUP
                        ]
                    ],
                    'ContainerServicegroup'         => [
                        'className'  => 'Container',
                        'foreignKey' => 'parent_id',
                        'conditions' => [
                            'ContainerServicegroup.containertype_id' => CT_SERVICEGROUP
                        ]
                    ],
                    'ContainerServicetemplategroup' => [
                        'className'  => 'Container',
                        'foreignKey' => 'parent_id',
                        'conditions' => [
                            'ContainerServicetemplategroup.containertype_id' => CT_SERVICETEMPLATEGROUP
                        ]
                    ],
                    'ContainerContactgroup'         => [
                        'className'  => 'Container',
                        'foreignKey' => 'parent_id',
                        'conditions' => [
                            'ContainerContactgroup.containertype_id' => CT_CONTACTGROUP
                        ]
                    ]
                ],
            ]
        );

        $containerDetails = $this->Container->find('first', [
            'recursive'  => -1,
            'contain'    => [
                'ContainerLocation'             => [
                    'fields'   => [
                        'ContainerLocation.id',
                        'ContainerLocation.name'
                    ],
                    'Location' => [
                        'fields' => [
                            'Location.id',
                            'Location.description'
                        ]
                    ],
                    'order'    => [
                        'ContainerLocation.name' => 'asc'
                    ]
                ],
                'ContainerNode'                 => [
                    'fields' => [
                        'ContainerNode.id',
                        'ContainerNode.name'
                    ],
                    'order'  => [
                        'ContainerNode.name' => 'asc'
                    ]
                ],
                'Host'                          => [
                    'fields' => [
                        'Host.id',
                        'Host.name',
                        'Host.description'
                    ],
                    'order'  => [
                        'Host.name' => 'asc'
                    ]
                ],
                'Hosttemplate'                  => [
                    'fields' => [
                        'Hosttemplate.id',
                        'Hosttemplate.name',
                        'Hosttemplate.description'
                    ],
                    'order'  => [
                        'Hosttemplate.name' => 'asc'
                    ]
                ],
                'Servicetemplate'               => [
                    'fields' => [
                        'Servicetemplate.id',
                        'Servicetemplate.template_name',
                        'Servicetemplate.name'
                    ],
                    'order'  => [
                        'Servicetemplate.name' => 'asc'
                    ]
                ],
                'Timeperiod'                    => [
                    'fields' => [
                        'Timeperiod.id',
                        'Timeperiod.name'
                    ],
                    'order'  => [
                        'Timeperiod.name' => 'asc'
                    ]
                ],
                'Hostdependency'                => [
                    'fields' => [
                        'Hostdependency.id'
                    ]
                ],
                'Servicedependency'             => [
                    'fields' => [
                        'Servicedependency.id'
                    ]
                ],
                'Hostescalation'                => [
                    'fields' => [
                        'Hostescalation.id'
                    ]
                ],
                'Serviceescalation'             => [
                    'fields' => [
                        'Serviceescalation.id'
                    ]
                ],
                'Contact'                       => [
                    'fields' => [
                        'Contact.id',
                        'Contact.name',
                        'Contact.description'
                    ],
                    'order'  => [
                        'Contact.name' => 'asc'
                    ]
                ],
                'ContainerContactgroup'         => [
                    'fields'       => [
                        'ContainerContactgroup.id',
                        'ContainerContactgroup.name'
                    ],
                    'Contactgroup' => [
                        'fields' => [
                            'Contactgroup.id',
                            'Contactgroup.description'
                        ]
                    ],
                    'order'        => [
                        'ContainerContactgroup.name' => 'asc'
                    ]
                ],
                'ContainerHostgroup'            => [
                    'fields'    => [
                        'ContainerHostgroup.id',
                        'ContainerHostgroup.name'
                    ],
                    'Hostgroup' => [
                        'fields' => [
                            'Hostgroup.id',
                            'Hostgroup.description'
                        ]
                    ],
                    'order'     => [
                        'ContainerHostgroup.name' => 'asc'
                    ]
                ],
                'ContainerServicegroup'         => [
                    'fields'       => [
                        'ContainerServicegroup.id',
                        'ContainerServicegroup.name'
                    ],
                    'Servicegroup' => [
                        'fields' => [
                            'Servicegroup.id',
                            'Servicegroup.description'
                        ]
                    ],
                    'order'        => [
                        'ContainerServicegroup.name' => 'asc'
                    ]
                ],
                'ContainerServicetemplategroup' => [
                    'fields'               => [
                        'ContainerServicetemplategroup.id',
                        'ContainerServicetemplategroup.name'
                    ],
                    'Servicetemplategroup' => [
                        'fields' => [
                            'Servicetemplategroup.id'
                        ]
                    ],
                    'order'                => [
                        'ContainerServicetemplategroup.name' => 'asc'
                    ]
                ]
            ],
            'conditions' => [
                'Container.id' => $id
            ]
        ]);
        $ModuleManager = new ModuleManager('DistributeModule');
        if ($ModuleManager->moduleExists()) {
            $SatelliteModel = $ModuleManager->loadModel('Satellite');
            $satellites = $SatelliteModel->find('all', [
                'recursive'  => -1,
                'fields'     => [
                    'Satellite.id',
                    'Satellite.name'
                ],
                'conditions' => [
                    'Satellite.container_id' => $id
                ],
                'order'      => [
                    'Satellite.name' => 'asc'
                ]
            ]);
            if (!empty($satellites)) {
                $containerDetails['Satellite'] = $satellites;
            }
        }
        $this->set(compact(['containerDetails']));
        $this->set('_serialize', ['containerDetails']);

    }
}

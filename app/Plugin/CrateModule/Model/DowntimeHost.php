<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

use itnovum\openITCOCKPIT\Core\DowntimeHostConditions;

class DowntimeHost extends CrateModuleAppModel {

    public $useDbConfig = 'Crate';
    public $useTable = 'host_downtimehistory';
    public $tablePrefix = 'statusengine_';

    /**
     * @param DowntimeHostConditions $Conditions
     * @param array $filterConditions
     * @return array
     */
    public function getQuery(DowntimeHostConditions $Conditions, $filterConditions = []) {
        $fields = [
            'DowntimeHost.author_name',
            'DowntimeHost.comment_data',
            'DowntimeHost.entry_time',
            'DowntimeHost.scheduled_start_time',
            'DowntimeHost.scheduled_end_time',
            'DowntimeHost.duration',
            'DowntimeHost.was_started',
            'DowntimeHost.internal_downtime_id',
            'DowntimeHost.was_cancelled',


            'Host.id',
            'Host.uuid',
            'Host.name',
            'Host.container_ids'
        ];

        $query = [
            'recursive' => -1,
            'fields'    => $fields,
            'joins'     => [
                [
                    'table'      => 'openitcockpit_hosts',
                    'type'       => 'INNER',
                    'alias'      => 'Host',
                    'conditions' =>
                        'Host.uuid = DowntimeHost.hostname',
                ],
            ],

            'conditions' => [
                'DowntimeHost.scheduled_start_time >' => $Conditions->getFrom(),
                'DowntimeHost.scheduled_start_time <' => $Conditions->getTo()
            ],

            'order' => $Conditions->getOrder(),
            'limit' => $Conditions->getLimit(),
        ];

        if ($Conditions->hideExpired()) {
            $query['conditions']['DowntimeHost.scheduled_end_time >'] = time();
        }

        if ($Conditions->hasContainerIds()) {
            $query['array_difference'] = [
                'Host.container_ids' =>
                    $Conditions->getContainerIds(),
            ];
        }

        $query['conditions'] = Hash::merge($query['conditions'], $filterConditions);
        if (isset($query['conditions']['DowntimeHost.was_started'])) {
            $query['conditions']['DowntimeHost.was_started'] = (bool)$query['conditions']['DowntimeHost.was_started'];
        }

        if (isset($query['conditions']['DowntimeHost.was_cancelled'])) {
            $query['conditions']['DowntimeHost.was_cancelled'] = (bool)$query['conditions']['DowntimeHost.was_cancelled'];
        }

        if ($Conditions->isRunning()) {
            $query['conditions']['DowntimeHost.scheduled_end_time >'] = time();
            $query['conditions']['DowntimeHost.was_started'] = true;
            $query['conditions']['DowntimeHost.was_cancelled'] = false;
        }


        return $query;
    }

    /**
     * @param DowntimeHostConditions $Conditions
     * @return array
     */
    public function getQueryForReporting(DowntimeHostConditions $Conditions) {
        $query = [
            'fields'     => [
                'DowntimeHost.author_name',
                'DowntimeHost.comment_data',
                'DowntimeHost.scheduled_start_time',
                'DowntimeHost.scheduled_end_time',
                'DowntimeHost.actual_end_time',
                'DowntimeHost.duration',
                'DowntimeHost.was_started',
                'DowntimeHost.was_cancelled',
                'Host.uuid'
            ],
            'joins'      => [
                [
                    'table'      => 'openitcockpit_hosts',
                    'type'       => 'INNER',
                    'alias'      => 'Host',
                    'conditions' =>
                        'Host.uuid = DowntimeHost.hostname',
                ],
            ],
            'order'      => $Conditions->getOrder(),
        ];

        if ($Conditions->includeCancelledDowntimes() === false) {
            $query['conditions']['DowntimeHost.was_cancelled'] = false;
        }

        if ($Conditions->hasHostUuids()) {
            $query['conditions']['DowntimeHost.hostname'] = $Conditions->getHostUuids();
        }


        $query['or'] = [
            ['? BETWEEN DowntimeHost.scheduled_start_time AND DowntimeHost.scheduled_end_time' => [$Conditions->getFrom()]],
            ['? BETWEEN DowntimeHost.scheduled_start_time AND DowntimeHost.scheduled_end_time' => [$Conditions->getTo()]],
            ['DowntimeHost.scheduled_start_time BETWEEN ? AND ?' => [$Conditions->getFrom(), $Conditions->getTo()]]
        ];

        return $query;
    }

    /**
     * @param int $internalDowntimeId
     * @return array
     */
    public function getHostUuidWithDowntimeByInternalDowntimeId($internalDowntimeId) {
        $query = [
            'fields'     => [
                'DowntimeHost.*',

            ],
            'conditions' => [
                'DowntimeHost.internal_downtime_id' => $internalDowntimeId
            ]
        ];

        $result = $this->find('first', $query);
        if (empty($result)) {
            return [];
        }

        return [
            'DowntimeHost' => $result['DowntimeHost'],
            'Host'         => [
                'uuid' => $result['DowntimeHost']['hostname']
            ]
        ];

    }

    /**
     * @param null $uuid
     * @param bool $isRunning
     * @return array|null
     */
    public function byHostUuid($uuid = null, $isRunning = false) {
        if ($uuid !== null) {

            $query = [
                'conditions' => [
                    'hostname' => $uuid,
                ],
                'order'      => [
                    'DowntimeHost.entry_time' => 'DESC',
                ],
            ];
            if ($isRunning) {
                $query['conditions']['DowntimeHost.scheduled_end_time >'] = time();
                $query['conditions']['DowntimeHost.was_started'] = true;
                $query['conditions']['DowntimeHost.was_cancelled'] = false;
            }

            $downtime = $this->find('first', $query);

            return $downtime;

        }

        return [];
    }

}


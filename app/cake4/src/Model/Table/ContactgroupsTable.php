<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ContactgroupsFilter;

/**
 * Contactgroups Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsToMany $Contacts
 *
 * @method \App\Model\Entity\Contactgroup get($primaryKey, $options = [])
 * @method \App\Model\Entity\Contactgroup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Contactgroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Contactgroup|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Contactgroup|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Contactgroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Contactgroup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Contactgroup findOrCreate($search, callable $callback = null, $options = [])
 */
class ContactgroupsTable extends Table {

    use PaginationAndScrollIndexTrait;
    use Cake2ResultTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) {
        parent::initialize($config);

        $this->setTable('contactgroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ])->setDependent(true);

        $this->belongsToMany('Contacts', [
            'className'        => 'Contacts',
            'foreignKey'       => 'contactgroup_id',
            'targetForeignKey' => 'contact_id',
            'joinTable'        => 'contacts_to_contactgroups',
            'saveStrategy'     => 'replace'
        ]);

    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) {
        $validator
            ->integer('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description', true);

        $validator
            ->requirePresence('contacts', true, __('You have to choose at least one contact.'))
            ->allowEmptyString('contacts', false)
            ->multipleOptions('contacts', [
                'min' => 1
            ], __('You have to choose at least one contact.'));

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->isUnique(['uuid']));
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param ContactgroupsFilter $ContactgroupsFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getContactgroupsIndex(ContactgroupsFilter $ContactgroupsFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all');
        $query->contain(['Containers']);
        $query->where($ContactgroupsFilter->indexFilter());

        $query->innerJoinWith('Containers', function ($q) use ($MY_RIGHTS) {
            if (!empty($MY_RIGHTS)) {
                return $q->where(['Containers.parent_id IN' => $MY_RIGHTS]);
            }
            return $q;
        });

        $query->disableHydration();
        $query->order($ContactgroupsFilter->getOrderForPaginator('Containers.name', 'asc'));


        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }

        return $result;
    }

    /**
     * @param int $id
     * @param array $contain
     * @return array
     */
    public function getContactgroupById($id, $contain = ['Containers', 'Contacts']) {
        $query = $this->find()
            ->where([
                'Contactgroups.id' => $id
            ])
            ->contain($contain)
            ->disableHydration()
            ->first();

        $result = $this->formatFirstResultAsCake2($query, true);
        unset($result['Container'], $result['Contactstocontactgroup']);
        return $result;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getContactgroupForEdit($id) {
        $query = $this->find()
            ->where([
                'Contactgroups.id' => $id
            ])
            ->contain([
                'Containers',
                'Contacts',
            ])
            ->disableHydration()
            ->first();


        $contact = $query;
        $contact['contacts'] = [
            '_ids' => Hash::extract($query, 'contacts.{n}.id')
        ];

        return [
            'Contactgroup' => $contact
        ];
    }

    /**
     * @param \CakeRequest $Request
     * @return array
     */
    public function getExtDataForChangelog(\CakeRequest $Request) {
        $extDataForChangelog = [
            'Contact' => []
        ];

        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');

        foreach ($ContactsTable->getContactsAsList($Request->data('Contactgroup.contacts._ids')) as $contactId => $contactName) {
            $extDataForChangelog['Contact'][] = [
                'id'   => $contactId,
                'name' => $contactName
            ];
        }

        return $extDataForChangelog;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Contactgroups.id' => $id]);
    }
}
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
?>
<div class="well no-padding">
	<?php echo $this->Form->create('Proxy', array(
		//'url' => '/login/login',
		'id' => 'proxy-form',
		'class' => 'smart-form client-form',
		'inputDefaults' => array(
			'wrapInput' => false,
			'label' => false,
			'div' => false
		)
	)); ?>
		<header><i class="fa fa-bolt"></i> <?php echo __('Proxy settings:'); ?></header>
		<fieldset>
			<div class"row">
				<section class="col col-10">
					<label class="label"><?php echo __('IP address'); ?></label>
					<label class="input"> <i class="icon-prepend fa fa-exchange"></i>
						<?php
						$proxyconf = array('placeholder' => 'proxy.example.org', 'disabled' => 'disabled');
						if(isset($proxy[0]['Proxy']['ipaddress'])):
							$proxyconf = array(
								'disabled' => 'disabled',
								'value' => $proxy[0]['Proxy']['ipaddress']
							);
						endif;
						
						echo $this->Form->input('ipaddress', $proxyconf); ?>
						<b class="tooltip tooltip-top-left"><i class="fa fa-exchange txt-color-teal"></i> <?php echo __('Please enter the address of your proxy server'); ?></b>
					</label>
				</section>
				<section class="col col-2">
					<label class="label">Port</label>
					<label class="input"> <i class="icon-prepend fa fa-terminal"></i>
						<?php
						$proxyconf = array('placeholder' => 3128, 'disabled' => 'disabled');
						if(isset($proxy[0]['Proxy']['port'])):
							$proxyconf = array(
								'disabled' => 'disabled',
								'value' => $proxy[0]['Proxy']['port']
							);
						endif;
						
						echo $this->Form->input('port', $proxyconf); ?>
						<b class="tooltip tooltip-top-left"><i class="fa fa-terminal txt-color-teal"></i> <?php echo __('Please enter the port of your proxy'); ?></b> 
					</label>
				</section>
				
				<?php
				$checked = '';
				if(isset($proxy[0]['Proxy']['enabled']) && $proxy[0]['Proxy']['enabled'] === true):
					$checked = 'checked=checked';
				endif;
				?>
				<section class="col col-12 pull-right">
					<label class="toggle">
						<input type="checkbox" <?php echo $checked; ?> disabled="disabled" name="checkbox-toggle">
						<i data-swchoff-text="OFF" data-swchon-text="ON"></i><?php echo __('Enable Proxy'); ?>
					</label>
				</section>
				
				
			</div>
		</fieldset>
		<?php if($this->Acl->hasPermission('edit')): ?>
			<footer>
				<a href="/proxy/edit" class="btn btn-default pull-left saveEditMode" title="Edit"><i class="fa fa-lock"></i></a>
			</footer>
		<?php endif; ?>
	<?php echo $this->Form->end(); ?>
</div>
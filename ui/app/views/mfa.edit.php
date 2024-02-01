<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2024 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * @var CView $this
 * @var array $data
 */

$form_action = (new CUrl('zabbix.php'))
	->setArgument('action', 'mfa.check')
	->getUrl();

$form = (new CForm('post', $form_action))
	->addItem(getMessages());

if (array_key_exists('mfaid', $data)) {
	$form->addVar('mfaid', $data['mfaid']);
}

// Enable form submitting on Enter.
$form->addItem((new CSubmitButton())->addClass(ZBX_STYLE_FORM_SUBMIT_HIDDEN));
$curl_warning = !$data['curl_error']
		? ''
		: (makeWarningIcon($data['curl_error']));

$form
	->addItem((new CFormGrid())
		->addItem([
			new CLabel(_('Type'), 'type'),
			new CFormField([
				(new CSelect('type'))
					->setFocusableElementId('type')
					->setValue($data['type'])
					->addOption(new CSelectOption(MFA_TYPE_TOTP, _('TOTP')))
					->addOption((new CSelectOption(MFA_TYPE_DUO, _('DUO Universal Prompt')))
						->setDisabled($data['curl_error'])),
				$curl_warning
			])
		])
		->addItem([
			(new CLabel(_('Name'), 'name'))->setAsteriskMark(),
			new CFormField(
				(new CTextBox('name', $data['name'], false, DB::getFieldLength('mfa', 'name')))
					->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
					->setAriaRequired()
					->setAttribute('autofocus', 'autofocus')
			)
		])
		->addItem([
			(new CLabel(_('Hash function'), 'hash_function')),
			(new CFormField(
				(new CSelect('hash_function'))
					->setFocusableElementId('hash_function')
					->setValue($data['hash_function'])
					->addOptions(CSelect::createOptionsFromArray([
						TOTP_HASH_SHA1 => _('SHA-1'),
						TOTP_HASH_SHA256 => _('SHA-256'),
						TOTP_HASH_SHA512 => _('SHA-512')
					]))
			))->setId('hash_function')
		])
		->addItem([
			(new CLabel(_('Code length'), 'code_length')),
			(new CFormField(
				(new CSelect('code_length'))
					->setFocusableElementId('code_length')
					->setValue($data['code_length'])
					->addOptions(CSelect::createOptionsFromArray([
						TOTP_CODE_LENGTH_6 => _('6'),
						TOTP_CODE_LENGTH_8 => _('8')
					]))
			))->setId('code_length')
		])
		->addItem([
			(new CLabel(_('API hostname'), 'api_hostname')),
			(new CFormField(
				(new CTextBox('api_hostname', $data['api_hostname'], false, DB::getFieldLength('mfa', 'api_hostname')))
					->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
			))->setId('api_hostname')
		])
		->addItem([
			(new CLabel(_('Client ID'), 'clientid')),
			(new CFormField(
				(new CTextBox('clientid', $data['clientid'], false, DB::getFieldLength('mfa', 'clientid')))
					->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
			))->setId('clientid')
		])
		->addItem([
			(new CLabel(_('Client secret'), 'client_secret')),
			(new CFormField($data['add_mfa_method'] == 0
				? [
					array_key_exists('client_secret', $data)
						? (new CVar('client_secret', $data['client_secret']))->removeId()
						: null,
					(new CSimpleButton(_('Change client secret')))
						->addClass(ZBX_STYLE_BTN_GREY)
						->setId('client-secret-btn'),
					(new CPassBox('client_secret', '', DB::getFieldLength('mfa', 'client_secret')))
						->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
						->addStyle('display: none;')
						->setAttribute('disabled', 'disabled')
				]
				: (new CPassBox('client_secret', '', DB::getFieldLength('mfa', 'client_secret')))
					->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
			))->setId('client_secret')
		])
		->addItem((new CScriptTag('mfa_edit.init();'))->setOnDocumentReady())
	);

if ($data['add_mfa_method']) {
	$title = _('New MFA method');
	$buttons = [
		[
			'title' => _('Add'),
			'class' => 'js-add',
			'keepOpen' => true,
			'isSubmit' => true,
			'action' => 'mfa_edit.submit();'
		]
	];
}
else {
	$title = _('MFA method');
	$buttons = [
		[
			'title' => _('Update'),
			'class' => 'js-update',
			'keepOpen' => true,
			'isSubmit' => true,
			'action' => 'mfa_edit.submit();'
		]
	];
}

$output = [
	'header' => $title,
	'body' => $form->toString(),
	'buttons' => $buttons,
	'script_inline' => getPagePostJs().
		$this->readJsFile('mfa.edit.js.php')
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);

<?php
/* Copyright (C) 2007-2016	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2011		Dimitri Mouillard			<dmouillard@teclib.com>
 * Copyright (C) 2020		Tobias Sekan				<tobias.sekan@startmail.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  Displays the log of actions performed in the module.
 *
 *  \file       htdocs/holiday/view_log.php
 *  \ingroup    holiday
 */

// Load Dolibarr environment
require '../main.inc.php';

// Security check (access forbidden for external user too)
if (!$user->hasRight('holiday', 'define_holiday') || $user->socid > 0) {
	accessforbidden();
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';

$action             = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction         = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$mode      = GETPOST('mode', 'alpha');
$show_files         = GETPOSTINT('show_files'); // Show files area generated by bulk actions ?
$confirm            = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel             = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect           = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage        = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'myobjectlist'; // To manage different context of search
$backtopage         = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss          = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$search_id          = GETPOST('search_id', 'alphanohtml');
$search_month       = GETPOSTINT('search_month');
$search_year        = GETPOSTINT('search_year');
$search_employee    = GETPOST('search_employee', "intcomma");
$search_validator   = GETPOST('search_validator', "intcomma");
$search_description = GETPOST('search_description', 'alphanohtml');
$search_type        = GETPOST('search_type', "intcomma");
$search_prev_solde  = GETPOST('search_prev_solde', 'alphanohtml');
$search_new_solde   = GETPOST('search_new_solde', 'alphanohtml');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "cpl.rowid";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

// Load translation files required by the page
$langs->loadLangs(array('users', 'other', 'holiday'));

// Initialize technical objects
$object = new Holiday($db);
$extrafields = new ExtraFields($db);
//$diroutputmassaction = $conf->mymodule->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('leavemovementlist')); // Note that conf->hooks_modules contains array

$arrayfields = array();
$arrayofmassactions = array();

if (!isModEnabled('holiday')) {
	accessforbidden('Module not enabled');
}

// Si l'utilisateur n'a pas le droit de lire cette page
if (!$user->hasRight('holiday', 'readall')) {
	accessforbidden();
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_id = '';
		$search_month = '';
		$search_year = '';
		$search_employee = '';
		$search_validator = '';
		$search_description = '';
		$search_type = '';
		$search_prev_solde = '';
		$search_new_solde = '';
		$toselect = array();
		$search_array_options = array();
	}

	if (GETPOST('button_removefilter_x', 'alpha')
		|| GETPOST('button_removefilter.x', 'alpha')
		|| GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha')
		|| GETPOST('button_search.x', 'alpha')
		|| GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	/*$objectclass='MyObject';
	$objectlabel='MyObject';
	$permissiontoread = $user->rights->mymodule->read;
	$permissiontodelete = $user->rights->mymodule->delete;
	$uploaddir = $conf->mymodule->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
	*/
}


// Definition of fields for lists
$arrayfields = array(
	'cpl.rowid' => array('label' => "ID", 'checked' => 1),
	'cpl.date_action' => array('label' => "Date", 'checked' => 1),
	'cpl.fk_user_action' => array('label' => "ActionByCP", 'checked' => 1),
	'cpl.fk_user_update' => array('label' => "UserUpdateCP", 'checked' => 1),
	'cpl.type_action' => array('label' => "Description", 'checked' => 1),
	'cpl.fk_type' => array('label' => "Type", 'checked' => 1),
	'cpl.prev_solde' => array('label' => "PrevSoldeCP", 'checked' => 1),
	'variation' => array('label' => "Variation", 'checked' => 1),
	'cpl.new_solde' => array('label' => "NewSoldeCP", 'checked' => 1),
);


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$holidaylogstatic = new stdClass();
$alltypeleaves = $object->getTypes(1, -1); // To have labels

$title = $langs->trans('CPTitreMenu');
llxHeader('', $title);

$sqlwhere = '';

if (!empty($search_year) && $search_year > 0) {
	if (!empty($search_month) && $search_month > 0) {
		$from_date  = dol_get_first_day($search_year, $search_month, 1);
		$to_date    = dol_get_last_day($search_year, $search_month, 1);
	} else {
		$from_date  = dol_get_first_day($search_year, 1, 1);
		$to_date    = dol_get_last_day($search_year, 12, 1);
	}

	$sqlwhere .= "AND date_action BETWEEN '".$db->idate($from_date)."' AND '".$db->idate($to_date)."'";
}

if (!empty($search_id) && $search_id > 0) {
	$sqlwhere .= natural_search('rowid', $search_id, 1);
}
if (!empty($search_validator) && $search_validator > 0) {
	$sqlwhere .= natural_search('fk_user_action', $search_validator, 1);
}
if (!empty($search_employee) && $search_employee > 0) {
	$sqlwhere .= natural_search('fk_user_update', $search_employee, 1);
}
if (!empty($search_description)) {
	$sqlwhere .= natural_search('type_action', $search_description);
}
if (!empty($search_type) && $search_type > 0) {
	$sqlwhere .= natural_search('fk_type', $search_type, 1);
}
if (!empty($search_prev_solde)) {
	$sqlwhere .= natural_search('prev_solde', $search_prev_solde, 1);
}
if (!empty($search_new_solde)) {
	$sqlwhere .= natural_search('new_solde', $search_new_solde, 1);
}

$sqlorder = $db->order($sortfield, $sortorder);

// Recent changes are more important than old changes
$log_holiday = $object->fetchLog($sqlorder, $sqlwhere); // Load $object->logs

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	//TODO: $result = $db->query($sql);
	//TODO: $nbtotalofrecords = $db->num_rows($result);
	$nbtotalofrecords = is_array($object->logs) ? count($object->logs) : 0;

	if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

// TODO: $num = $db->num_rows($resql);
$num = is_array($object->logs) ? count($object->logs) : 0;

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if (!empty($search_id)) {
	$param .= '&search_status='.urlencode($search_status);
}
if (!empty($search_month) && $search_month > 0) {
	$param .= '&search_month='.urlencode((string) ($search_month));
}
if (!empty($search_year) && $search_year > 0) {
	$param .= '&search_year='.urlencode((string) ($search_year));
}
if (!empty($search_validator) && $search_validator > 0) {
	$param .= '&search_validator='.urlencode((string) ($search_validator));
}
if (!empty($search_employee) && $search_employee > 0) {
	$param .= '&search_employee='.urlencode((string) ($search_employee));
}
if (!empty($search_description)) {
	$param .= '&search_description='.urlencode($search_description);
}
if (!empty($search_type) && $search_type > 0) {
	$param .= '&search_type='.urlencode((string) ($search_type));
}
if (!empty($search_prev_solde)) {
	$param .= '&search_prev_solde='.urlencode($search_prev_solde);
}
if (!empty($search_new_solde)) {
	$param .= '&search_new_solde='.urlencode($search_new_solde);
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$newcardbutton = dolGetButtonTitle($langs->trans('MenuAddCP'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/holiday/card.php?action=create', '', $user->hasRight('holiday', 'write'));
// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
print_barre_liste($langs->trans('LogCP'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_hrm', 0, $newcardbutton, '', $limit, 0, 0, 1);

print '<div class="info">'.$langs->trans('LastUpdateCP').': ';

$lastUpdate = $object->getConfCP('lastUpdate');
if ($lastUpdate) {
	$monthLastUpdate = $lastUpdate[4].$lastUpdate[5];
	$yearLastUpdate = $lastUpdate[0].$lastUpdate[1].$lastUpdate[2].$lastUpdate[3];
	print '<strong>'.dol_print_date($db->jdate($object->getConfCP('lastUpdate')), 'dayhour', 'tzuser').'</strong>';
	print '<br>';
	print $langs->trans("MonthOfLastMonthlyUpdate").': <strong>'.$yearLastUpdate.'-'.$monthLastUpdate.'</strong>';
} else {
	print $langs->trans('None');
}
print '</div>';
print '<br>';

$moreforfilter = '';
$morefilter = '';
$disabled = 0;
$include = '';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'" id="tablelines3">';

print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}

// Filter Id
if (!empty($arrayfields['cpl.rowid']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="maxwidth50" name="search_id" value="'.$search_id.'"></td>';
}

// Filter: Date
if (!empty($arrayfields['cpl.date_action']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat valignmiddle maxwidth25" type="text" maxlength="2" name="search_month" value="'.dol_escape_htmltag($search_month).'">';
	print $formother->selectyear($search_year, 'search_year', 1, 10, 5, 0, 0, '', 'valignmiddle width75', true);
	print '</td>';
}

// Filter: Validator
if (!empty($arrayfields['cpl.fk_user_action']['checked'])) {
	$validator = new UserGroup($db);
	$excludefilter = $user->admin ? '' : 'u.rowid <> '.((int) $user->id);
	$valideurobjects = $validator->listUsersForGroup($excludefilter, 1);
	$valideurarray = array();
	foreach ($valideurobjects as $val) {
		$valideurarray[$val] = $val;
	}

	print '<td class="liste_titre">';
	print $form->select_dolusers($search_validator, "search_validator", 1, "", 0, $valideurarray, '', 0, 0, 0, $morefilter, 0, '', 'maxwidth200');
	print '</td>';
}

// Filter: User
if (!empty($arrayfields['cpl.fk_user_update']['checked'])) {
	print '<td class="liste_titre">';
	print $form->select_dolusers($search_employee, "search_employee", 1, "", $disabled, $include, '', 0, 0, 0, $morefilter, 0, '', 'maxwidth200');
	print '</td>';
}

// Filter: Description
if (!empty($arrayfields['cpl.type_action']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="maxwidth50" name="search_description" value="'.$search_description.'">';
	print '</td>';
}

// Filter: Type
if (!empty($arrayfields['cpl.fk_type']['checked'])) {
	$arraytypeleaves = array();
	foreach ($alltypeleaves as $key => $val) {
		$labeltoshow = ($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']);
		$arraytypeleaves[$val['rowid']] = $labeltoshow;
	}

	print '<td class="liste_titre">';
	print $form->selectarray('search_type', $arraytypeleaves, $search_type, 1, 0, 0, '', 0, 0, 0, '', '', 1);
	print '</td>';
}

// Filter: Previous balance
if (!empty($arrayfields['cpl.prev_solde']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input type="text" class="maxwidth50" name="search_prev_solde" value="'.$search_prev_solde.'">';
	print '</td>';
}

// Filter: Variation (only placeholder)
if (!empty($arrayfields['variation']['checked'])) {
	print '<td class="liste_titre"></td>';
}

// Filter: New Balance
if (!empty($arrayfields['cpl.new_solde']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input type="text" class="maxwidth50" name="search_new_solde" value="'.$search_new_solde.'">';
	print '</td>';
}

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>';

print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
if (!empty($arrayfields['cpl.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['cpl.rowid']['label'], $_SERVER["PHP_SELF"], 'cpl.rowid', '', '', '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cpl.date_action']['checked'])) {
	print_liste_field_titre($arrayfields['cpl.date_action']['label'], $_SERVER["PHP_SELF"], 'date_action', '', '', '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['cpl.fk_user_action']['checked'])) {
	print_liste_field_titre($arrayfields['cpl.fk_user_action']['label'], $_SERVER["PHP_SELF"], 'fk_user_action', '', '', '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cpl.fk_user_update']['checked'])) {
	print_liste_field_titre($arrayfields['cpl.fk_user_update']['label'], $_SERVER["PHP_SELF"], 'fk_user_update', '', '', '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cpl.type_action']['checked'])) {
	print_liste_field_titre($arrayfields['cpl.type_action']['label'], $_SERVER["PHP_SELF"], 'type_action', '', '', '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cpl.fk_type']['checked'])) {
	print_liste_field_titre($arrayfields['cpl.fk_type']['label'], $_SERVER["PHP_SELF"], 'fk_type', '', '', '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cpl.prev_solde']['checked'])) {
	print_liste_field_titre($arrayfields['cpl.prev_solde']['label'], $_SERVER["PHP_SELF"], 'prev_solde', '', '', '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['variation']['checked'])) {
	print_liste_field_titre($arrayfields['variation']['label'], $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['cpl.new_solde']['checked'])) {
	print_liste_field_titre($arrayfields['cpl.new_solde']['label'], $_SERVER["PHP_SELF"], 'new_solde', '', '', '', $sortfield, $sortorder, 'right ');
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print '</tr>';


$j = 0;
while ($j < ($page * $limit)) {
	$obj = next($object->logs);
	$j++;
}

$i = 0;
while ($i < min($num, $limit)) {
	//TODO: $obj = $db->fetch_object($resql);
	$obj = current($object->logs);
	if (empty($obj)) {
		break;
	}

	$holidaylogstatic->id = $obj['rowid'];
	$holidaylogstatic->date = $obj['date_action'];
	$holidaylogstatic->validator = $obj['fk_user_action'];
	$holidaylogstatic->employee = $obj['fk_user_update'];
	$holidaylogstatic->description = $obj['type_action'];
	$holidaylogstatic->type = $obj['fk_type'];
	$holidaylogstatic->balance_previous = $obj['prev_solde'];
	$holidaylogstatic->balance_new = $obj['new_solde'];

	print '<tr class="oddeven">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}

	// Id
	if (!empty($arrayfields['cpl.rowid']['checked'])) {
		print '<td>'.$holidaylogstatic->id.'</td>';
	}

	// Date
	if (!empty($arrayfields['cpl.date_action']['checked'])) {
		print '<td style="text-align: center">'.$holidaylogstatic->date.'</td>';
	}

	// Validator
	if (!empty($arrayfields['cpl.fk_user_action']['checked'])) {
		$user_action = new User($db);
		$user_action->fetch($holidaylogstatic->validator);
		print '<td>'.$user_action->getNomUrl(-1).'</td>';
	}

	// Emloyee
	if (!empty($arrayfields['cpl.fk_user_update']['checked'])) {
		$user_update = new User($db);
		$user_update->fetch($holidaylogstatic->employee);
		print '<td>'.$user_update->getNomUrl(-1).'</td>';
	}

	// Description
	if (!empty($arrayfields['cpl.type_action']['checked'])) {
		print '<td class="tdoverflowmax400" title="'.dol_escape_htmltag($holidaylogstatic->description).'">'.dol_escape_htmltag($holidaylogstatic->description).'</td>';
	}

	// Type
	if (!empty($arrayfields['cpl.fk_type']['checked'])) {
		$label = '';
		if (!empty($alltypeleaves[$holidaylogstatic->type])) {
			if ($alltypeleaves[$holidaylogstatic->type]['code'] && $langs->trans($alltypeleaves[$holidaylogstatic->type]['code']) != $alltypeleaves[$holidaylogstatic->type]['code']) {
				$label = $langs->trans($alltypeleaves[$holidaylogstatic->type]['code']);
			} else {
				$label = $alltypeleaves[$holidaylogstatic->type]['label'];
			}
		}

		print '<td>';
		print $label ? $label : $holidaylogstatic->type;
		print '</td>';
	}

	// Previous balance
	if (!empty($arrayfields['cpl.prev_solde']['checked'])) {
		print '<td style="text-align: right;">'.price2num($holidaylogstatic->balance_previous, 5).' '.$langs->trans('days').'</td>';
	}

	// Variation
	if (!empty($arrayfields['variation']['checked'])) {
		$delta = price2num($holidaylogstatic->balance_new - $holidaylogstatic->balance_previous, 5);
		print '<td style="text-align: right;">';
		if ($delta > 0) {
			print '<span class="stockmovemententry fontsizeunset">+'.$delta.'</span>';
		} else {
			print '<span class="stockmovementexit fontsizeunset">'.$delta.'</span>';
		}
		print '</td>';
	}

	// New Balance
	if (!empty($arrayfields['cpl.new_solde']['checked'])) {
		print '<td style="text-align: right;">'.price2num($holidaylogstatic->balance_new, 5).' '.$langs->trans('days').'</td>';
	}

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}

	print '</tr>';

	$i++;
	next($object->logs);
}

if ($log_holiday == '2') {
	print '<tr class="opacitymedium">';
	print '<td colspan="10" class="opacitymedium">'.$langs->trans('NoRecordFound').'</td>';
	print '</tr>';
}

print '</table>';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();

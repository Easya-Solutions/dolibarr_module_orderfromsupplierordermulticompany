<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/orderfromsupplierordermulticompany.php
 * 	\ingroup	orderfromsupplierordermulticompany
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
require('../config.php');

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/orderfromsupplierordermulticompany.lib.php';
//require_once "../class/myclass.class.php";
// Translations
$langs->load("orderfromsupplierordermulticompany@orderfromsupplierordermulticompany");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "orderfromsupplierordermulticompanySetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = orderfromsupplierordermulticompanyAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104200Name"),
    0,
    "orderfromsupplierordermulticompany@orderfromsupplierordermulticompany"
);

// Setup page goes here
	echo $langs->trans("orderfromsupplierordermulticompanySetupPage");

	$ATMdb=new TPDOdb;
	if(isset($_REQUEST['action']) && $_REQUEST['action']=='save') {

	    if(!empty($_REQUEST['TLine'])) {
			foreach($_REQUEST['TLine'] as $id=>$TValues) {

			    $TValues['fk_entity'] = GETPOST('TLine_'.$TValues['rowid'].'_fk_entity', 'int');
			    $TValues['fk_soc'] = GETPOST('TLine_'.$TValues['rowid'].'_fk_soc', 'int');

				$o=new TTELink;
				if($id>0 ) $o->load($ATMdb, $id);
				else{

					if($TValues['fk_soc']>0 && $TValues['fk_entity']>0) {
						null;
					}
					else{
						continue; // non valide on passe au cycle suivant
					}

				}


				$o->set_values($TValues);

				$o->entity = $conf->entity;

				if(isset($TValues['delete'])) {
					$o->delete($ATMdb);
				}
				else {
					$o->save($ATMdb);
				}
			}
		}

	}


	$TLink = TTELink::getList($ATMdb);

	$form=new TFormCore($_SERVER['PHP_SELF'],'form1','POST');
	$form->Set_typeaff('edit');
	echo $form->hidden('action', 'save');

	?>
	<table class="liste">
		<tr class="liste_titre">
			<td><?php echo $langs->trans('Company'); ?></td>
			<td><?php echo $langs->trans('Entity'); ?></td>
			<td><?php echo $langs->trans('Delete'); ?> ?</td>
		</tr>
	<?php

	$html=new Form($db);
	$m=new ActionsMulticompany($db);



	foreach($TLink as $link) {

		?>
			<tr>
				<td><?php print $html->select_company($link->fk_soc,'TLine_'.$link->rowid.'_fk_soc','',1);  ?></td>
				<td><?php print $m->select_entities($link->fk_entity,'TLine_'.$link->rowid.'_fk_entity' ); ?></td>
				<td><input type="hidden" name="TLine[<?php echo $link->rowid ?>][rowid]" value="<?php echo $link->rowid ?>" /><input type="checkbox" value="1" name="TLine[<?php echo $link->rowid ?>][delete]"/></td>
			</tr>
		<?php

	}
		?><tr class="liste_titre">
				<td><?php print $html->select_company(-1,'TLine_0_fk_soc','',1);  ?></td>
				<td><?php print $m->select_entities(-1,'TLine_0_fk_entity' ); ?></td>
				<td><input type="hidden" name="TLine[0][rowid]" value="0" /> <?php $langs->trans('Nouvelle liaison'); ?></td>
			</tr>
	</table>
	<?php

	echo '<div class="tabsAction">'. $form->btsubmit("Enregistrer", "bt_submit") .'</div>';

	echo $form->end_form();

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("Parameters").'</th>'."\n";
	print '<th align="center" width="20">&nbsp;</th>';
	print '<th align="center" width="'.$width.'"></th>'."\n";
	print '</tr>';

	print '<tr>';
	print '<td>'.$langs->trans('OFSOMC_CREATE_ORDER_TRIGGER').'</td>';
	print '<td></td>';
	print '<td><form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="action" value="set_OFSOMC_CREATE_ORDER_TRIGGER">';
	$TActionCreate = array(
		'ORDER_SUPPLIER_VALIDATE' => $langs->trans('OFSOMC_ORDER_SUPPLIER_VALIDATE')
		, 'ORDER_SUPPLIER_SUBMIT' => $langs->trans('OFSOMC_ORDER_SUPPLIER_SUBMIT'));
	print $html->selectarray('OFSOMC_CREATE_ORDER_TRIGGER',$TActionCreate, $conf->global->OFSOMC_CREATE_ORDER_TRIGGER);
	print '<input class="butAction" type="submit" value="'.$langs->trans('Save').'">';
	print '</form></td>';
	print '</tr>';

	print '</table>';

llxFooter();

$db->close();

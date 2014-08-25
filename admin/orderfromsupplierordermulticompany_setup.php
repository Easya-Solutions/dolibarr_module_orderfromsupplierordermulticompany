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
				<td><?php print $html->select_company($link->fk_soc,'TLine['.$link->rowid.'][fk_soc]','',1);  ?></td>
				<td><?php print $m->select_entities($link->fk_entity,'TLine['.$link->rowid.'][fk_entity]' ); ?></td>
				<td><input type="checkbox" value="1" name="TLine[<?php echo $link->rowid ?>][delete]"/></td>
			</tr>		
		<?		
		
	}
		?><tr class="liste_titre">
				<td><?php print $html->select_company(-1,'TLine[0][fk_soc]','',1);  ?></td>
				<td><?php print $m->select_entities(-1,'TLine[0][fk_entity]' ); ?></td>
				<td>Nouvelle liaison</td>
			</tr>		
	</table>
	<?php 
	
	echo '<div class="tabsAction">'. $form->btsubmit("Enregistrer", "bt_submit") .'</div>';
	
	echo $form->end_form();	

llxFooter();

$db->close();
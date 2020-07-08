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
 * 	\file		core/triggers/interface_99_modMyodule_Mytrigger.class.php
 * 	\ingroup	orderfromsupplierordermulticompany
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modMymodule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class Interfaceorderfromsupplierordermulticompanytrigger
{

    private $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are empty functions."
            . "They have no effect."
            . "They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'orderfromsupplierordermulticompany@orderfromsupplierordermulticompany';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Users

       if (($action === 'ORDER_SUPPLIER_VALIDATE' && empty($conf->global->OFSOM_STATUS)) || $action === $conf->global->OFSOM_STATUS) {

          $this->_cloneOrder($object);

       } elseif ($action === 'ORDER_SUPPLIER_RECEIVE'){

           require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

           if(!empty($conf->global->OFSOM_LINK_STATUSSUPPLIERORDER_ORDERCHILD))
           {
               $sql = "SELECT fk_target FROM ".MAIN_DB_PREFIX."element_element WHERE fk_source ='".$object->id."' AND targettype = 'commande' AND sourcetype ='commandefourn'";
               $resql = $this->db->query($sql);

               if ($resql)
               {
                   if($this->db->num_rows($resql) > 0)
                   {
                       $obj = $this->db->fetch_object($resql);
                       $id_ordertarget = $obj->fk_target;

                       $commande = new Commande($this->db);
                       $res = $commande->fetch($id_ordertarget);

                       if ($res > 0)
                       {
                           $commande->setStatut(Commande::STATUS_CLOSED);
                           $res = $commande->update($user);
                           if ($res > 0)
                           {
                               return 1;
                           }
                           else
                           {
                               return -1;
                           }
                       }
                       else
                       {
                           return -1;
                       }
                   }
                   else {
                       return 0;
                   }
               }
               else
               {
                   return -1;
               }
           }
       }
       else if ($action === 'LINEORDER_UPDATE' && !empty($conf->global->OFSOM_UPDATE_LINE_SOURCE)) {
       	if($object->oldline->qty != $object->qty || $object->oldline->subprice != $object->subprice) {
	        $conf->supplierorderdet->enabled = 1;
	        $object->fetchObjectLinked(null, 'supplierorderdet', $object->id,  $object->element,  'OR',  1,  'sourcetype', 0);
			if(!empty($object->linkedObjectsIds['supplierorderdet'])) {
				dol_include_once('/fourn/class/fournisseur.commande.class.php');
				$commande = new Commande($object->db);
				$commande->fetch($object->fk_commande);
				$res = $object->db->query("SELECT fk_entity FROM ".MAIN_DB_PREFIX."thirdparty_entity WHERE entity=".$conf->entity." AND fk_soc=".$commande->socid.' AND fk_entity <> '.$conf->entity);
				$obj = $object->db->fetch_object($res);
				if(!empty($obj->fk_entity)) {
					foreach ($object->linkedObjectsIds['supplierorderdet'] as $supplierOrderLineId) {
						$supplierOrderLine = new CommandeFournisseurLigne($object->db);
						$supplierOrderLine->fetch($supplierOrderLineId);
						$tabprice = calcul_price_total($object->qty, $object->subprice, $supplierOrderLine->remise_percent, $supplierOrderLine->tva_tx, $supplierOrderLine->localtax1_tx, $supplierOrderLine->localtax2_tx, 0, 'HT', $supplierOrderLine->info_bits, $supplierOrderLine->product_type, $supplierOrderLine->thirdparty, array(), 100, $supplierOrderLine->multicurrency_tx, $supplierOrderLine->pu_ht_devise);

						$supplierOrderLine->qty = $object->qty;
						$supplierOrderLine->subprice = $object->subprice;
						$supplierOrderLine->total_ht  = $tabprice[0];
						$supplierOrderLine->total_tva = $tabprice[1];
						$supplierOrderLine->total_ttc = $tabprice[2];
						$supplierOrderLine->update();

						//MAJ des totaux
						$tmpentity = $conf->entity;
						$conf->entity = $obj->fk_entity;
						$supplierOrder = new CommandeFournisseur($object->db);
						$supplierOrder->fetch($supplierOrderLine->fk_commande);
						$supplierOrder->update_price('', 'auto');

						$conf->entity = $tmpentity;
					}
				}
			}

        }
       }
       else if($action === 'LINEORDER_INSERT') {
       	    if(!empty($object->origin_id)) $object->add_object_linked($object->origin, $object->origin_id);
       }
       else if($action === 'LINEORDER_DELETE') {
	       $object->deleteObjectLinked();
       }

        return 0;
    }

    private function _cloneOrder ($object) {

        global $conf;

        define('INC_FROM_DOLIBARR', true);
        dol_include_once('/orderfromsupplierordermulticompany/config.php');

        $db=& $this->db;

        $res = $db->query("SELECT fk_entity FROM ".MAIN_DB_PREFIX."thirdparty_entity WHERE entity=".$conf->entity." AND fk_soc=".$object->socid.' AND fk_entity <> '.$conf->entity);
        $obj = $db->fetch_object($res);

        if ($obj->fk_entity > 0)
        {
            TTELink::cloneOrder($object->id, $obj->fk_entity);
        } else {
            return -1;
        }
    }
}

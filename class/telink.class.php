<?php
/*
 * Lien entre thirdpartie et entité
 * 
 */
	class TTELink extends TObjetStd {
		
		function __construct() {
			
			parent::set_table(MAIN_DB_PREFIX.'thirdparty_entity');
			parent::add_champs('fk_entity,fk_soc,entity','type=entier;index;');//fk_soc_leaser
					
			parent::_init_vars();
			parent::start();
			
		}
		
		static function getList(&$ATMdb) {
			global $conf;
			
			$Tab=array();
			
			$Tab = $ATMdb->ExecuteAsArray("SELECT rowid,fk_soc,fk_entity FROM ".MAIN_DB_PREFIX."thirdparty_entity WHERE entity IN (".$conf->entity.") ORDER BY rowid ASC");
			
			return $Tab;
		}
		
		static function cloneOrder($idOrderSource, $toEntity) {
		global $db,$conf, $user, $mc;
		
			$cf=new CommandeFournisseur($db);
			$cf->fetch($idOrderSource);
			
			//$res = $db->query("SELECT fk_soc FROM ".MAIN_DB_PREFIX."thirdparty_entity WHERE fk_entity=".$toEntity." AND entity=".$conf->entity );	
			$res = $db->query("SELECT fk_soc FROM ".MAIN_DB_PREFIX."thirdparty_entity WHERE entity=".$toEntity." AND fk_entity=".$conf->entity ); //Attention, cela permet de créer la commande sur la société correspondant à l'entité emettrice

			$obj = $db->fetch_object($res);	
				
			if($obj->fk_soc>0) {
				
				dol_include_once('/commande/class/commande.class.php');
				
				$res2 = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."commande
						 WHERE entity=".$toEntity." AND ref_client='".$cf->ref."'" );	

				$obj2 = $db->fetch_object($res2);	
				if($obj2->rowid>0) {
					// la facture commande déjà dans le système en face. On la supprime
					$o=new Commande($db);
					
					$previous_entity = $conf->entity;
					$conf->entity = $toEntity;
					if($o->fetch($obj2->rowid)>0) {
						$o->delete($user);
						
					}
					
					$conf->entity = $previous_entity;
					
				}
				
				$o=new Commande($db);
				$o->date = date('Y-m-d H:i:s');
				$o->ref_client = $cf->ref;
				$o->socid = $obj->fk_soc;

				$o->fk_project = $cf->fk_project; //TODO check if it's shared project

				$o->lines = array();

				foreach($cf->lines as $line) {
					$lineOrder = new OrderLine($db);

					$TPropertiesToClone = array('desc', 'subprice', 'qty', 'tva_tx', 'vat_src_code', 'localtax1_tx', 'localtax2_tx', 'fk_product', 'remise_percent', 'info_bits', 'fk_remise_except', 'date_start', 'date_end', 'product_type', 'rang', 'special_code', 'fk_parent_line', 'fk_fournprice', 'pa_ht', 'label', 'array_options', 'fk_unit', 'id');

					foreach($TPropertiesToClone as $property) {
						$lineOrder->{ $property } = $line->{ $property };
					}

					$o->lines[] = $lineOrder;
				}

				if(!empty($conf->global->OFSOM_DONT_FORCE_BUY_PRICE_WITH_SELL_PRICE)) {
					$oldval = $conf->global->ForceBuyingPriceIfNull;
					$conf->global->ForceBuyingPriceIfNull = 0;
				}

				if($o->create($user)<0) {
					
					var_dump($o);
					exit("Erreur création commande");
				}
				else{

					if(!empty($conf->nomenclature->enabled)) {
						$orderID = $o->id;
						$o = new Commande($db);
						$res = $o->fetch($orderID); // Rechargement pour récupérer les bons IDs des lignes

						dol_include_once('/nomenclature/class/nomenclature.class.php');
						$PDOdb = new TPDOdb;
						
						foreach($cf->lines as $k=>&$line) {
							$n=new TNomenclature;
							$n->loadByObjectId($PDOdb, $line->id, $cf->element);
							if($n->iExist) {
								$n->reinit();
								$n->fk_object = $o->lines[$k]->id;
								$n->object_type = $o->element;
								$n->save($PDOdb);
							}
							
						}		
							
						
					}

					// Le changement d'entité doit se faire après le changement d'entité, sinon, le fetch échoue
					$res = $db->query("UPDATE ".MAIN_DB_PREFIX."commande
						 SET entity=".$toEntity."
						 WHERE rowid=".$o->id ); // on transporte la commande dans l'autre entité

                    //Lien entre la commande fournisseur et la commande client dans la table element_element
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
                    $sql .= "fk_source";
                    $sql .= ", sourcetype";
                    $sql .= ", fk_target";
                    $sql .= ", targettype";
                    $sql .= ") VALUES (";
                    $sql .= $idOrderSource;
                    $sql .= ", 'commandefourn'";
                    $sql .= ", ".$o->id;
                    $sql .= ", 'commande'";
                    $sql .= ")";
                    $res = $db->query($sql);

				}
				
				
                                if(!empty($conf->global->OFSOM_DONT_FORCE_BUY_PRICE_WITH_SELL_PRICE)) $conf->global->ForceBuyingPriceIfNull = $oldval;
				
			}
			
			
			
			
		}
		
		
	}

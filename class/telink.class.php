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
		global $db,$conf, $user;
		
			$cf=new CommandeFournisseur($db);
			$cf->fetch($idOrderSource);
			
			
			$res = $db->query("SELECT fk_soc FROM ".MAIN_DB_PREFIX."thirdparty_entity WHERE entity=".$toEntity." AND fk_entity=".$conf->entity );	
			$obj = $db->fetch_object($res);	
				
			if($obj->fk_soc>0) {
				
				dol_include_once('/commande/class/commande.class.php');
				
				$res2 = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."commande
						 WHERE entity=".$toEntity." AND ref_client='".$cf->ref."'" );	
				$obj2 = $db->fetch_object($res2);	
				if($obj2->rowid>0) {
					// la facture existe déjà dans le système en face. On la supprime
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
				$o->lines = $cf->lines;
				
				if($o->create($user)<0) {
					
					var_dump($o);
					exit("Erreur création commande");
				}
				else{
					
					$res = $db->query("UPDATE ".MAIN_DB_PREFIX."commande
						 SET entity=".$toEntity." 
						 WHERE rowid=".$o->id ); // on transporte la commande dans l'autre entité	
				
					
				}
				
				
				
			}
			
			
			
			
		}
		
		
	}

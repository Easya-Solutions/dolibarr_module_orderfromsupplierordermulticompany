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
			
			$Tab = $ATMdb->ExecuteAsArray("SELECT rowid,fk_soc,entity FROM ".MAIN_DB_PREFIX."thirdparty_entity WHERE entity IN (".$conf->entity.") ORDER BY rowid ASC");
			
			return $Tab;
		}
		
		
	}

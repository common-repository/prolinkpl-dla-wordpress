<?php
/*
Plugin Name: ProLink.pl dla WordPress
Description: Zobacz jakie reklamy z platformy ProLink.pl są wyświetlane w Twoim serwisie
Author: Tomasz Topa
Author URI: http://tomasz.topa.pl
Version: 1.0.1
License: GPL2

	Copyright 2012  Tomasz Topa  (email : tomasz [at] topa.pl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/* 
	Actions
*/
add_action('admin_menu', 'pldwp_menu_add');



/*
	Functions
*/


// Dodanie opcji do menu
function pldwp_menu_add() {
  add_menu_page('ProLink.pl dla WordPress', 'ProLink.pl dla WordPress', 'administrator', __FILE__, 'pldwp_main'); 
}


// Główna funkcja
function pldwp_main() {
	if (!current_user_can('read'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
  
	pldwp_autofind_id();
	
	if($_POST['pldwp_siteid']){
		pldwp_options_save();
	}
  
  
	
	echo '<div class="wrap">
	 
	<h2>ProLink.pl dla WordPress</h2>
	
	<h3>Konfiguracja: identyfikator serwisu przypisany przez ProLink.pl:</h3>
		<form name="pldwp_config" method="post" action="">
			<p><input type="text" name="pldwp_siteid" value="'.htmlspecialchars(get_option('pldwp_siteid')).'" size="70"> <input type="submit" class="button-primary" value="Zapisz"></p>
		</form>
		<p>Nie korzystasz jeszcze z ProLink? <a href="http://bit.ly/prolinkpl" target="_blank">Zarejestruj się już teraz</a> i zacznij zarabiać na swojej stronie WWW!</p>
		<p>&nbsp;</p>
	';
	
	if(pldwp_check_file()){
		pldwp_get_links();
		
	} else {
		echo '
			<p><strong>Nie odnaleziono pliku z bazą danych ProLink.pl</strong>.</p>
			<p>Upewnij się, że poprawnie został podany identyfikator serwisu</p>
		';
	}
  
	echo '</div><!--wrap-->';
}


function pldwp_autofind_id(){
	if(!get_option('pldwp_siteid')){
		if($pldwp_dir=opendir($_SERVER['DOCUMENT_ROOT'])){
			while (false !== ($pldwp_item = readdir($pldwp_dir))) {
				if(strlen($pldwp_item)==44 && strstr($pldwp_item,'prolink_')){
					$pldwp_item2=str_replace(array('prolink_','.txt'),'',$pldwp_item);
					update_option('pldwp_siteid', $pldwp_item2);
				}
			}
		}
	}
}

function pldwp_options_save(){
	update_option('pldwp_siteid', mysql_escape_string($_POST['pldwp_siteid']));
}

function pldwp_check_file(){
	if(file_exists($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'prolink_'.get_option('pldwp_siteid').'.txt')){
		return true;
	} else { 
		return false;
	}
}

function pldwp_get_links(){
	$pldwp_links=file($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'prolink_'.get_option('pldwp_siteid').'.txt');
	$plwdp_links2=unserialize($pldwp_links[0]);
	
	if(count($plwdp_links2)==0){
		$pldwp_content='<p>Brak reklam wyświetlanych na stronie.</p>';
	} else {
		$pldwp_subpages[md5(get_option('pldwp_siteid').'/')]=site_url().'/';
		$pldwp_args = array( 'post_type' => array('post','page'), 'numberposts' => -1, 'post_status' => 'publish', 'post_parent' => null ); 
		$pldwp_allposts = get_posts($pldwp_args);
		foreach($pldwp_allposts as $pldwp_post){
			$pldwp_subpages[md5(get_option('pldwp_siteid').str_replace(site_url(),'',get_permalink($pldwp_post->ID)))]=get_permalink($pldwp_post->ID);
		}
		
		$pldwp_categories=get_terms('category');
		foreach($pldwp_categories as $pldwp_category){
			$pldwp_subpages[md5(get_option('pldwp_siteid').str_replace(site_url(),'',get_term_link($pldwp_category->slug,$pldwp_category->taxonomy)))]=get_term_link($pldwp_category->slug,$pldwp_category->taxonomy);
		}
		$pldwp_tags=get_terms('post_tag');
		foreach($pldwp_tags as $pldwp_tag){
			$pldwp_subpages[md5(get_option('pldwp_siteid').str_replace(site_url(),'',get_term_link($pldwp_category->slug,$pldwp_category->taxonomy)))]=get_term_link($pldwp_category->slug,$pldwp_category->taxonomy);
		}
		
		
		$pldwp_content2.= '
			<table id="pldwp_links" class="tablesorter">
			<thead>
				<tr>
					<th>Podstrona</th>
					<th>Wygląd reklamy</th>
					<th>URL docelowy</th>
				</tr>
			</thead>
			<tbody>
		';
		foreach($plwdp_links2 as $plwdp_links_page_key=>$plwdp_links_page){
			$plwdp_stats_pages[]=$plwdp_links_page_key;
			
			foreach($plwdp_links_page as $plwdp_links_items){
				$plwdp_stats_ads[]=$plwdp_links_items['link'];
				$pldwp_stats_link_parts=parse_url($plwdp_links_items['link']);
				$pldwp_stats_links[]=str_replace('www.','',$pldwp_stats_link_parts['host']);
				$pldwp_content2.='
					<tr>
						<td>';
							if($pldwp_subpages[$plwdp_links_page_key]){
								$pldwp_content2.='<a href="'.$pldwp_subpages[$plwdp_links_page_key].'" target="_blank">'.str_replace(site_url(),'',$pldwp_subpages[$plwdp_links_page_key]).'</a>';
							} else {
								$pldwp_content2.='[ ? ]';
							}
							
							$pldwp_content2.='
						</td>
						<td>
							'.$plwdp_links_items['pre'].' <u>'.$plwdp_links_items['text'].'</u> '.$plwdp_links_items['post'].'
						</td>
						<td>
							<a target="_blank" href="'.$plwdp_links_items['link'].'">'.$plwdp_links_items['link'].'</a>
						</td>
					</tr>
				';
			}
			
		}
		$pldwp_links_uq=array_unique($plwdp_stats_ads);
		$pldwp_links_uq2=array_unique($pldwp_stats_links);
		$pldwp_links_uq3=array_unique($plwdp_stats_pages);
		$pldwp_content.='<li>Liczba podstron z reklamami: '.count($pldwp_links_uq3).'</li>';
		$pldwp_content.='<li>Liczba reklam w serwisie: '.count($plwdp_stats_ads).'</li>';
		$pldwp_content.='<li>Liczba unikalnych linków: '.count($pldwp_links_uq).'</li>';
		$pldwp_content.='<li>Liczba unikalnych reklamodawców: '.count($pldwp_links_uq2).'</li></ul>';
		
	}

		$pldwp_content2.= '
		
			</tbody>
		</table>
		
		<link rel="stylesheet" href="'.plugin_dir_url(__FILE__).'assets/style.css" type="text/css" media="screen" />
		<script src="'.plugin_dir_url(__FILE__).'assets/jquery.tablesorter.min.js"></script>
		<script>
			jQuery(document).ready(function() { 
				jQuery("#pldwp_links").tablesorter(); 
			}); 
		</script>
		';
	
		
		
	
	echo '<h3>Reklamy w serwisie:</h3>';
	echo $pldwp_content;
	echo '<h3>Lista reklam:</h3>';
	echo $pldwp_content2;
}

?>

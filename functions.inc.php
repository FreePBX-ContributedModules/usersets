<?php


/* 	Generates callerid checks for usersets
	We call this with retrieve_conf
*/
function usersets_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $asterisk_conf;
	global $usersets_conf; // our usersets object (created in retrieve_conf)
	switch($engine) {
		case "asterisk":
			$allusersets = usersets_list();
			if(is_array($allusersets)) {
				foreach($allusersets as $item) {
					$id = "macro-usersets-" . $item['usersets_id'];
					$ext->add($id, 's', '', new ext_macro('user-callerid'));

					// do trust list first - if in both lists this one will take priority
					$trustarray = explode("\n",$item['trustlist']);
					if ($trustarray[0] == "") {unset($trustarray[0]);}
					foreach($trustarray as $trust) {
						$ext->add($id, 's', '', new ext_gotoif('$["${CALLERID(number)}" = "'.trim($trust).'"]','trusted'));
					}

					$autharray = explode("\n",$item['authlist']);
					if ($autharray[0] == "") {unset($autharray[0]);}
					foreach($autharray as $auth) {
						$ext->add($id, 's', '', new ext_gotoif('$["${CALLERID(number)}" = "'.trim($auth).'"]','needauth'));
					}
					$ext->add($id, 's', '', new ext_answer());
					$ext->add($id, 's', '', new ext_wait(1));
					$ext->add($id, 's', '', new ext_playback('cancelled'));
					$ext->add($id, 's', '', new ext_macro('hangupcall'));
					$ext->add($id, 's', 'needauth', new ext_answer());
					$ext->add($id, 's', '', new ext_wait(1));
					$ext->add($id, 's', '', new ext_macro('get-vmcontext','${CALLERID(number)}'));
					$ext->add($id, 's', '', new ext_vmauthenticate('${CALLERID(number)}@${VMCONTEXT}'));
					$ext->add($id, 's', 'trusted', new ext_noop('${CALLERID(number)} access approved'));

				}
			}

		break;
	}
}

function usersets_hookGet_config($engine) {
	global $ext;
	switch($engine) {
		case "asterisk":
			$hooklist = usersets_list();
			if(is_array($hooklist)) {
				foreach($hooklist as $thisitem) {

					// get the used_by field
					if(empty($thisitem['used_by'])) {
						$usedby = "";
					} else {
						$usedby = $thisitem['used_by'];
					}

					// create an array from usedby
					$arrUsedby = explode(',',$usedby);

					if(is_array($arrUsedby)){
						foreach($arrUsedby as $strUsedby){
							// if it's an outbound route
							if(strpos($strUsedby,'routing_') !== false) {
								$route = substr($strUsedby,8);
								$context = 'outrt-'.$route;

								// get all the routes that are in this context
								$routes = core_routing_getroutepatterns($route);

								// we need to manipulate each route/extension
								foreach($routes as $rt) {
									//strip the pipe out as that's what we use for the dialplan extension
									$extension = str_replace('|','',$rt);
									// If there are any wildcards in there, add a _ to the start
									if (preg_match("/\.|z|x|\[|\]/i", $extension)) { $extension = "_".$extension; }
									$ext->splice($context, $extension, 0, new ext_macro('usersets-'. $thisitem['usersets_id']));
								}

							}
						}
					}

				}
			}
		break;
	}
}


//get the existing usersets
function usersets_list() {
	$results = sql("SELECT * FROM usersets","getAll",DB_FETCHMODE_ASSOC);
	if(is_array($results)){
		foreach($results as $result){
			// check to see if we have a dept match for the current AMP User.
			if (checkDept($result['deptname'])){
				// return this item's dialplan destination, and the description
				$allowed[] = $result;
			}
		}
	}
	if (isset($allowed)) {
		return $allowed;
	} else {
		return null;
	}
}

function usersets_get($id){
	$results = sql("SELECT * FROM usersets WHERE usersets_id = '$id'","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}

function usersets_del($id){
	global $asterisk_conf;

	$results = sql("DELETE FROM usersets WHERE usersets_id = '$id'","query");
}

function usersets_add($post){
	if(!usersets_chk($post))
		return false;
	extract($post);
	if(empty($description)) $description = 'Unnamed';
	$results = sql("INSERT INTO usersets (description,deptname,trustlist,authlist) values (\"$description\",\"$deptname\",\"$trustlist\",\"$authlist\")");
}

function usersets_edit($id,$post){
	if(!usersets_chk($post))
		return false;
	extract($post);
	if(empty($description)) $description = 'Unnamed';
	$results = sql("UPDATE usersets SET description = \"$description\", deptname = \"$deptname\", trustlist = \"$trustlist\", authlist = \"$authlist\" WHERE usersets_id = \"$id\"");
}


// ensures post vars is valid
function usersets_chk($post){
	return true;
}

//removes a userset from a route and shifts priority for all outbound routing usersets
function usersets_adjustroute($route,$action,$routeuserset='',$direction='',$newname='') {
    $priority = (int)substr($route,0,3);
    //create a selection of available usersets
    $usersets = usersets_list();
	// loop through all the usersets
	if(is_array($usersets)){
		foreach($usersets as $userset) {

			// get the used_by field
			if(empty($userset['used_by'])) {
				$usedby = "";
			} else {
				$usedby = $userset['used_by'];
			}

			// remove the target if it's already in this row's used_by field
			//$usedby = str_replace("routing_{$route}","",$usedby);

			// create an array from usedby
			$arrUsedby = explode(',',$usedby);

			for($i=0;$i<count($arrUsedby);$i++) {
				if (substr($arrUsedby[$i],0,8)=='routing_') {
                    switch($action){
                        case 'delroute':
                    		if ($arrUsedby[$i] == "routing_{$route}") {
								unset($arrUsedby[$i]);
		              		}
                    		$usedbypriority = (int)substr($arrUsedby[$i],8,3);
							$usedbyroute = substr($arrUsedby[$i],12);
                    		if ($usedbypriority > $priority) {
		                        $newpriority = str_pad($usedbypriority - 1, 3, "0", STR_PAD_LEFT);
                        		$arrUsedby[$i] = 'routing_'.$newpriority.'-'.$usedbyroute;
							}
						break;
                        case 'prioritizeroute';
                        	$addpriority = ($direction=='up')?-1:1;
                    		$usedbypriority = (int)substr($arrUsedby[$i],8,3);
							$usedbyroute = substr($arrUsedby[$i],12);
                    		if ($priority + $addpriority == $usedbypriority) {
		                        $newpriority = str_pad($priority, 3, "0", STR_PAD_LEFT);
                        		$arrUsedby[$i] = 'routing_'.$newpriority.'-'.$usedbyroute;
							}
                		    if ($arrUsedby[$i] == "routing_{$route}") {
		                        $newpriority = str_pad($priority + $addpriority, 3, "0", STR_PAD_LEFT);
                        		$arrUsedby[$i] = 'routing_'.$newpriority.'-'.$usedbyroute;
		              		}

						break;
                        case 'renameroute';
                    		if ($arrUsedby[$i] == "routing_{$route}") {
		                        $newpriority = str_pad($priority, 3, "0", STR_PAD_LEFT);
                        		$arrUsedby[$i] = 'routing_'.$newpriority.'-'.$newname;
		              		}
						break;
                        case 'editroute';
                        	$usedbyroute = (int)substr($arrUsedby[$i],12);
                    		if ($arrUsedby[$i] == "routing_{$route}") {
								unset($arrUsedby[$i]);
							}
                        break;
					}
				}
			}

			// save the route in the selected user set
			if ($routeuserset == $userset['usersets_id'] && $action == 'editroute') {
				$arrUsedby[] = 'routing_'.$route;
			}

			// remove any duplicates
			$arrUsedby = array_values(array_unique($arrUsedby));

			// create a new string
			$strUsedby = implode($arrUsedby,',');

			// Insure there's no leading or trailing commas
			$strUsedby = trim ($strUsedby, ',');


			// store the used_by column in the DB
			sql("UPDATE usersets SET used_by = \"{$strUsedby}\" WHERE usersets_id = \"{$userset['usersets_id']}\"");
		}
	}
}

// provide hook for routing
function usersets_hook_core($viewing_itemid, $target_menuid) {
	switch ($target_menuid) {
		// only provide display for outbound routing
		case 'routing':
			//create a selection of available usersets
			$usersets = usersets_list();
			$hookhtml = '
				<tr>
					<td><a href="#" class="info">'._("Permitted User Set").'<span>'._('Optional: Select a Permitted User set to use. If using this option, leave the Route Password field blank.').'</span></a>:</td>
					<td>
						<select name="usersets">
							<option value="">&nbsp;</option>
			';

			if (is_array($usersets))
			{
				foreach($usersets as $item) {
					if (isset($viewing_itemid) && $viewing_itemid <> '' && strpos($item['used_by'], "routing_{$viewing_itemid}") !== false) {
						$selected = "selected=\"selected\"";
					} else {
						$selected = '';
					}
					$hookhtml .= "<option value={$item['usersets_id']} ".$selected.">{$item['description']}</option>";
				}
			}
			$hookhtml .= '
						</select>
					</td>
				</tr>
			';
			return $hookhtml;
		break;
		default:
				return false;
		break;
	}
}

function usersets_hookProcess_core($viewing_itemid, $request) {

	// Record any hook selections made by target modules
	// We'll add these to the userset's "used_by" column in the format <targetmodule>_<viewing_itemid>
	// multiple targets could select a single userset, so we'll comma delimiter them

	// this is really a crappy way to store things.
	// Any module that is hooked by usersets when submitted will result in all the "used_by" fields being re-written
	switch ($request['display']) {
        case 'routing':
		// if routing was using post for the form (incl delete), i wouldn't need all these conditions
		//		if(isset($request['Submit']) || (isset($request['action']) && ($request['action'] == "delroute" || $request['action'] == "prioritizeroute" || $request['action'] == "renameroute"))) {

			$action = (isset($request['action']))?$request['action']:null;
			$route = $viewing_itemid;
			if (isset($request['reporoutekey']) && $action == 'prioritizeroute') {
                $outbound_routes = core_routing_getroutenames();
				$route = $outbound_routes[(int)$request['reporoutekey']][0];
            }
			if (isset($request['Submit']) ) {
				$action = (isset($action))?$action:'editroute';
        	}
			if (isset($action)) {
            	$direction = (isset($request['reporoutedirection']))?$request['reporoutedirection']:null;
                $newname = (isset($request['newroutename']))?$request['newroutename']:null;
				usersets_adjustroute($route,$action,$request['usersets'],$direction,$newname);
			}

        break;
	}
}


?>

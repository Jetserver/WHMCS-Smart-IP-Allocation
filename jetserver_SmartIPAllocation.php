<?php
/*
*
* Smart IP Allocation
* Created By Idan Ben-Ezra
*
* Copyrights @ Jetserver Web Hosting
* www.jetserver.net
*
* Hook version 1.0.3
*
**/

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

/*********************
 Smart IP Allocation Settings
*********************/
function jetserverSmartIPAllocation_settings()
{
	/*
	* For more information and examples please go to https://docs.jetapps.com/category/whmcs-addons/whmcs-smart-ip-allocation
	**/
	$output['exclude_servers_ids'] = array(); // server ids to exclude from the IP check * leave empty to use all servers
	$output['only_group_ids'] = array(); // use only servers on the following groups * leave empty to use all servers groups

	return $output;
}
/********************/
	
function jetserverSmartIPAllocation_checkServerWithFreeIPs($server_details)
{
	$url = "{$server_details['serverhttpprefix']}://{$server_details['serverhostname']}:{$server_details['serverport']}/scripts/rebuildpool";

	$password_type = $server_details['serveraccesshash'] ? 'WHM' : 'Basic';
	$password = $password_type == 'WHM' ? preg_replace("'(\r|\n)'", "", $server_details['serveraccesshash']) : $server_details['serverpassword'];

	$authorization = "Authorization: {$password_type} {$server_details['serverusername']}:{$password}";

	$curl = curl_init();

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array($authorization));
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($curl);

	if ($result === false) 
	{
		logModuleCall("Smart IP Allocation", "Check For Free IPs", $url, curl_error($curl), $result);
		return 0;
	}

	logModuleCall("Smart IP Allocation", "Check For Free IPs", $url, '', $result);

	curl_close($curl);

	if(preg_match("/system has ([0-9]+)/i", $result, $match))
	{
		return intval($match[1]);
	}

	return 0;
}

function jetserverSmartIPAllocation_selectServer($vars)
{
	if($vars['params']['moduletype'] == 'cpanel' && $vars['params']['configoption6'] == 'on' && !jetserverSmartIPAllocation_checkServerWithFreeIPs($vars['params']))
	{

		$settings = jetserverSmartIPAllocation_settings();

		$settings['exclude_servers_ids'][] = $vars['params']['serverid'];

		$sql = "SELECT s.*, g.groupid as gid
			FROM tblservers as s
			INNER JOIN tblservergroupsrel as g 
			ON s.id = g.serverid
			WHERE s.id NOT IN ('" . implode("','", $settings['exclude_servers_ids']) . "')
			" . (sizeof($settings['only_group_ids']) ? "AND g.groupid IN ('" . implode("','", $settings['only_group_ids']) . "')" : '');
		$result = mysql_query($sql);

		while($server_details = mysql_fetch_assoc($result)) 
		{
			$check = jetserverSmartIPAllocation_checkServerWithFreeIPs(array(
				'serverhttpprefix'	=> $server_details['secure'] == 'on' ? 'https' : 'http',
				'serverhostname'	=> $server_details['hostname'],
				'serverport'		=> $server_details['port'] ? $server_details['port'] : ($server_details['secure'] == 'on' ? '2087' : '2086'),
				'serveraccesshash'	=> $server_details['accesshash'],
				'serverpassword'	=> $server_details['password'],
				'serverusername'	=> $server_details['username'],
			));

			if($check)
			{
				$sql = "UPDATE tblhosting 
					SET server = '{$server_details['id']}'
					WHERE id = '{$vars['params']['serviceid']}'";
				mysql_query($sql);	

				break;
			}
		}
		mysql_free_result($result);
	}
}

add_hook('PreModuleCreate', 0, 'jetserverSmartIPAllocation_selectServer');

?>

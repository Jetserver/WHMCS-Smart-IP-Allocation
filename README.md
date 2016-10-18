# WHMCS-Smart-IP-Allocation

This WHMCS hook will help you smartly utilize the use of dedicated IPs assigned to your servers.

Once an order is placed with a dedicated IP the hook will verify that the target server has enough free ips.
If the target server ran out of IP addresses, the hook will search in other servers for free IPs. Once found, the new server will be re-assigned to the order, and the hosting account will be created on the new target server.

Everything is done automatically in the background while the order is placed !
All you need to do is to place the file in the hooks folder !

# Installation

Edit the hook file with a simple code editor (notepad++ is recommended).
Set the exclude server/group ids, empty the vars if you want to use all servers. (example below)

Upload it to your WHMCS hooks folder (“includes/hooks“).

Code examples –
This example shows how to exclude server ids 5 & 6, and will search for servers only in group id 4 –

```
$output['exclude_servers_ids'] = array(5,6); 
$output['only_group_ids'] = array(4); 
```

# More Information

https://docs.jetapps.com/category/whmcs-addons/whmcs-smart-ip-allocation

-----------------------------------
 The "Usersets" Module for FreePBX
-----------------------------------

This module provides user based access control for outbound routes


Operation
---------

If the use of a userset is specified by an outbound route then the route will not be accessible unless the caller is listed in the userset. 
If not listed the caller will hear the audible prompt "Cancelled" and the call will terminate

Within a userset there are two types of users:
(i) Users that are trusted - These users need to provide no authentication. The fact that they are calling from a trusted extension number gives them access to the outbound route.
(ii) Users that need authentication - These users need to provide authentication to demonstrate that they are who they claim to be. These users are prompted for their voicemail password before being given access to the outbound route. 

If this module is enabled then it hooks into the outbound routes page (in the same way as the pinsets module). All existing usersets are displayed in a list box on the page.


Preconditions
-------------

This module expects the ext_vmauthenticate class to be in extensions.class.php as per FreePBX Ticket #2777. 
If not the modules functions.inc.php will need to be modified to generate the VMAuthenticate dialplan command itself e.g.
$command = "VMAuthenticate(" .($mailbox ? $mailbox : ) .($context ? '@'.$context : ) .($options ? '|'.$options : ) .")"


Open Issues
-----------

A caller's number is tested in turn against each entry in the userset. For large usersets this can be a slow process. More time sensitive users should be put near the top of a userset list.


Author
------
nick.lewis@atltelecom.com
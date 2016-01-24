# IPSAbsene

This module will simulate presence by setting the status of a group of objects. 
The module will create the groups automatically and for each group a delay (in seconds) can be specified.
Additionally the number of random seconds which should be added or removed from the delay can be configured.
Every group contains a category "Links", which must only contain links to variables or instances which should be turned on or off.
The "Turn on" property specifies, whether this group should turn the linked items on or off.

"Speed" can be used for testing the whole simulation. Speed 100 will simulate 1 hour as 36 seconds.
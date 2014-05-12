hlanganisa
==========

php Interface to add hosts to LConf without using LConf.

LConf is a LDAP based configuration tool for the Icinga Monitoring software.

The script connects to the LDAP where LConf configuration is stored and shows all structuralObjects available for hosts. A user can select the structuralObject and fill out a form with hostname, host alias, ip address and an optional comment.
The host gets added to LConf. The script keeps a log which hosts where created. The optional comment is available only in the log and is meant to send a message to the monitoring administrators to inform them about special needs (e.g. extra checks) for the newly created host.

This script was made for a Netways customer during my work as a consultant at Netways. Since this is a pre-release I will not check it in into Netways own git. Where this script will be hosted when it gets released is still to be discussed.

Since the name Icinga is a zulu word a co-worker thought it would be a good idea to name this script in zulu, too. Hence the name. Thanks for the inspiration, Christoph.

more resources
--------------

You can find the API documentation at <http://devel.widhalm.or.at/mirror/pub/docs/phpdoc/hlanganisa/>

changelog
---------

* 0.0.1 : initial commit to github.
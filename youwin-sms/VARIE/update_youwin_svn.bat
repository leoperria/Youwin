@echo off
echo ### Aggiornamento youwin
svn commit -m autocommit
plink -l root -pw gMLS1-qJwf 62.149.168.179 "cd /var/www/vhosts/default/htdocs/youwin/; svn update"




Usage
=============
You should not need to edit PHRets_CREA.php.
Open download.php
- Edit in your RETS Username and RETS Password.
- Change $TimeBackPull to '-2 days' to only receive the last 48 hours worth of data
- Run the script on your server (command line: php -f download.php).
- You will see the script find the total listings and begin paging through results.
- You will find @TODO markers where you need to add your own code, handle the listing data, etc.

Credits
=============

The Real Estate Transaction Standard
http://www.reso.org/rets

PHRets developed by Troy Davisson.
https://github.com/troydavisson/PHRETS

Thanks
=============

Thanks to Jason Graham for providing support on behalf of CREA.
